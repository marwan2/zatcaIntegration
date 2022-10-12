<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class XMLInvoice extends Model
{
    private $schema = 'http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd';
    public static $base_dir = 'xmls';
    public $invoice;
    public $business;
    private $trans_no;

    public function getSchema() {
        return $this->schema;
    }

    public function isValidSchema($dom=null) {
        if($dom && $dom->schemaValidate($this->getSchema())) {
            return true;
        }
        return false;
    }

    public function setNo($trans_no) {
        $this->trans_no = $trans_no;
    }

    public function getBusiness() {
        return $this->business;
    }

    public function setBusiness($business) {
        $this->business = $business;
    }

    public function getInvoice() {
        return $this->invoice;
    }

    public function setInvoice($invoice) {
        $this->invoice = $invoice;
    }

    public function getXML() {
        // Missing data
        /*
            Business: 
                country ISO2
                Street name
                Building no
                City
                Postal code
        */

        $invoice = $this->getInvoice();
        
        $supplierCompany = $this->businessNode();
        $clientCompany = $this->customerNode();
        $invoiceLines = $this->invoiceLines();
        $invoiceTaxes = $this->invoiceTaxes();
        $legalMonetaryTotal = $this->legalMonetaryTotal();
        $paymentMeans = $this->paymentMeans();

        $invoiceTypeCode = \App\Invoice::INVOICE;
        $invoiceTypeCodeName = \App\Invoice::getTypeCodeName('01');

        $issue_date = new \DateTime($invoice['order_date']);
        $due_date = new \DateTime($invoice['due_date']);
        $invoice_id = $invoice['ref'];
        $curr_code = $invoice['customer']['curr_code'] ?? 'SAR';
        $curr_code = 'SAR';

        $docReference = (new \NumNum\UBL\ContractDocumentReference())->setId($invoice['ref']);
        $additionalDocRef = $this->additionalDocumentReference();
        $invoice_uuid = \App\Invoice::generateUUID();

        // Invoice object
        $invoiceXML = new \NumNum\UBL\Invoice();
        $invoiceXML->setId($invoice_id)
            ->setUUID($invoice_uuid)
            ->setInvoiceTypeCode($invoiceTypeCode, $invoiceTypeCodeName)
            ->setCopyIndicator(false)
            ->setIssueDate($issue_date)
            ->setDueDate($due_date)
            ->setAccountingSupplierParty($supplierCompany)
            ->setAccountingCustomerParty($clientCompany)
            ->setInvoiceLines($invoiceLines)
            ->setLegalMonetaryTotal($legalMonetaryTotal)
            ->setTaxTotal($invoiceTaxes)
            ->setDocumentCurrencyCode($curr_code)
            ->setTaxCurrencyCode($curr_code)
            ->setPaymentMeans($paymentMeans)
            ->setProfileID('reporting:1.0')
            ->setNote($this->clean($invoice['comments']))
        ;

        if($curr_code) {
            $invoiceXML->addTaxTotal($this->onlyTaxTotal());
        }

        $ref_ICV = $this->additionalDocumentReference('ICV');
        $ref_PIH = $this->additionalDocumentReference('PIH');
        $ref_QR = $this->additionalDocumentReference('QR');
        
        $invoiceXML->addAdditionalDocumentReference($ref_ICV);
        $invoiceXML->addAdditionalDocumentReference($ref_PIH);
        $invoiceXML->addAdditionalDocumentReference($ref_QR);

        // Use \NumNum\UBL\Generator to generate an XML string
        $generator = new \NumNum\UBL\Generator();
        $outputXMLString = $generator->invoice($invoiceXML, $curr_code);

        return $outputXMLString;
    }

    // Supplier Party
    public function businessNode() {
        $business = $this->getBusiness();
        $invoice = $this->getInvoice();

        $supplier_name = $business->name;
        $country_iso2 = $business->country_code;
        $street_name = $business->street_name;
        $building_no = $business->building_no;
        $city_name = $business->city;
        $postal_zone = $business->postal_code;
        $plot_inden = $business->additional_no;
        $district = $business->district;
        $companyID = $this->getBusiness()->trn;
        $identificationID = $business->identification_id;

        // Address country
        $country = (new \NumNum\UBL\Country())
            ->setIdentificationCode($country_iso2);

        // Full address
        $supplierAddress = (new \NumNum\UBL\Address())
            ->setStreetName($street_name)
            ->setBuildingNumber($building_no)
            ->setCityName($city_name)
            ->setPostalZone($postal_zone)
            ->setCountry($country);

        if($plot_inden) { 
            $supplierAddress = $supplierAddress->setPlotIdentification($plot_inden);
        }
        if($district) { 
            $supplierAddress = $supplierAddress->setDistrict($district);
        }

        $legalEntity = $this->legalEntity();
        $partyTaxScheme = $this->partyTaxScheme($companyID, 'VAT');

        // Supplier company node
        $supplierCompany = (new \NumNum\UBL\Party())
            ->setName($supplier_name)
            ->setPhysicalLocation($supplierAddress)
            ->setPostalAddress($supplierAddress)
            ->setLegalEntity($legalEntity)
            ->setPartyTaxScheme($partyTaxScheme);

        if($identificationID) {
            $supplierCompany->setPartyIdentificationId($identificationID);
            $supplierCompany->setPartyIdentificationSchemeId($business->identification_scheme);
        }

        return $supplierCompany;
    }

    public function customerNode() {
        $business = $this->getBusiness();
        $invoice = $this->getInvoice();

        $max_limit = 127;

        $customer_name = $invoice['customer']['cust_ref'] ?? '';
        $customer_ref = $invoice['customer']['debtor_ref'] ?? $customer_name;
        $customer_email = $invoice['customer']['email'];
        $customer_phone = $invoice['customer']['phone'];
        $customer_fax = $invoice['customer']['fax'];
        
        $country_iso2 = $invoice['customer']['iso2'] ?? 'SA';
        $street_name = $this->clean($invoice['customer']['address'], $max_limit);
        $building_no = $this->clean($invoice['customer']['address'], $max_limit);
        $city_name = $invoice['customer']['city'] ?? 'CITY';
        $postal_zone = $invoice['customer']['postal_code'] ?? '13245';
        $additional_number = $this->clean($invoice['customer']['address'], $max_limit);

        // Address country
        $customer_country = (new \NumNum\UBL\Country())
            ->setIdentificationCode($country_iso2);

        // Full address
        $customerAddress = (new \NumNum\UBL\Address())
            ->setStreetName($street_name)
            ->setBuildingNumber($building_no)
            ->setCityName($city_name)
            ->setPostalZone($postal_zone)
            ->setCountry($customer_country);

        // Fix me: get real data for customers from SA
        if($country_iso2 == 'SA') {
            $district = 'ABC Dist';
            $additionalNo = '4574';
            $additionalStreetName = '4513'; // 4 digits only

            $customerAddress->setPlotIdentification($additionalStreetName);
            $customerAddress->setDistrict($district);
            if($additionalNo) {
                $customerAddress->setAdditionalStreetName($additionalNo);
            }
        }

        // Client contact node
        $clientContact = (new \NumNum\UBL\Contact())
            ->setName($customer_name)
            ->setElectronicMail($customer_email)
            ->setTelephone($customer_phone)
            ->setTelefax($customer_fax);

        // party tax Scheme
        $partyTaxScheme = $this->partyTaxScheme(null, 'VAT');

        // Client company node
        $clientCompany = (new \NumNum\UBL\Party())
            ->setName($customer_name)
            ->setPostalAddress($customerAddress)
            ->setContact($clientContact);

        if($partyTaxScheme) {
            $clientCompany->setPartyTaxScheme($partyTaxScheme);
        }

        $legalEntity = $this->legalEntity($customer_ref);
        $clientCompany->setLegalEntity($legalEntity);

        return $clientCompany;
    }

    // Fix me: Get real taxIncluded value for invoice (from invoice sales_type)
    public function invoiceLines() {
        $invoice = $this->getInvoice();
        $trn = $this->getBusiness()->trn;

        $calc = new Calc;
        $calc->setInvoice($this->getInvoice())->setTaxIncluded(false);
        $taxes = Invoice::getTaxforItems($this->getBusiness());
        if($taxes) {
            $calc->setTaxes($taxes);
        }

        $invoiceLines = [];
        foreach($invoice['line_items'] as $item) {
            $item_unit = (isset($item['units']) && $item['units']=='each') ? \NumNum\UBL\UnitCode::PIECE : \NumNum\UBL\UnitCode::UNIT;
            $lineExtAmount = $calc->calcItemTotal($item);
            $roundingAmount = $lineExtAmount + $item['tax'];

            // InvoicePeriod
            $invoicePeriod = (new \NumNum\UBL\InvoicePeriod())
                ->setStartDate(new \DateTime());

            // Price
            $price = (new \NumNum\UBL\Price())
                ->setBaseQuantity($item['qty'])
                ->setUnitCode($item_unit)
                ->setPriceAmount($item['price']);

            // Invoice Line tax totals
            $lineTaxTotal = (new \NumNum\UBL\TaxTotal())
                ->setTaxAmount($item['tax'])
                ->setRoundingAmount($roundingAmount);

            $classifiedTaxCategory = $this->itemTaxCategory($item);

            // Product
            $productItem = (new \NumNum\UBL\Item())
                ->setName($item['stock_id'])
                ->setDescription($item['description'])
                ->setSellersItemIdentification($trn)
                ->setClassifiedTaxCategory($classifiedTaxCategory);

            $invoiceLines[] = (new \NumNum\UBL\InvoiceLine())
                ->setId($item['id'])
                ->setItem($productItem)
                ->setInvoicePeriod($invoicePeriod)
                ->setPrice($price)
                ->setTaxTotal($lineTaxTotal)
                ->setInvoicedQuantity($item['qty_dispatched'])
                ->setLineExtensionAmount($lineExtAmount);
        }

        return $invoiceLines;
    }

    public function legalMonetaryTotal() {
        $invoice = $this->getInvoice();

        $line_ext_amount = $invoice['sub_total'];
        $payable_amount = $invoice['display_total'];
        $tax_total = $invoice['tax_total'];
        $tax_inclusive_amount = $invoice['display_total'];
        $tax_exclusive_amount = $invoice['display_total'] - $tax_total;

        $legalMonetaryTotal = (new \NumNum\UBL\LegalMonetaryTotal())
            ->setLineExtensionAmount($line_ext_amount)
            ->setTaxExclusiveAmount($tax_exclusive_amount)
            ->setTaxInclusiveAmount($tax_inclusive_amount)
            ->setAllowanceTotalAmount(0)
            ->setPayableAmount($payable_amount);

        return $legalMonetaryTotal;
    }

    public function invoiceTaxes() {
        $invoice = $this->getInvoice();
        $taxAmount = $invoice['tax_total'];
        $taxableAmount = $invoice['display_total'];

        $invoice_taxes = $invoice['taxes'][0] ?? null;
        $tname = 'VAT';
        $percent = 0.0;
        $tax_total = $invoice['tax_total'];

        if($invoice_taxes) {
            $tname = $invoice_taxes['name'];
            $percent = $invoice_taxes['rate'] / 100;
            $tax_total = $invoice_taxes['amount'];
        }

        // Tax scheme
        $taxScheme = (new \NumNum\UBL\TaxScheme())
            ->setId(0);

        // Total Taxes
        $taxCategory = (new \NumNum\UBL\TaxCategory())
            ->setId(0)
            ->setName($tname)
            ->setPercent($percent)
            ->setTaxScheme($taxScheme);

        $taxSubTotal = (new \NumNum\UBL\TaxSubTotal())
            ->setTaxableAmount($taxableAmount)
            ->setTaxAmount($taxAmount)
            ->setTaxCategory($taxCategory);


        $taxTotal = (new \NumNum\UBL\TaxTotal())
            ->addTaxSubTotal($taxSubTotal)
            ->setTaxAmount($tax_total);

        return $taxTotal;
    }

    // if taxCurrencyCode provided 
    public function onlyTaxTotal() {
        $invoice = $this->getInvoice();
        $tax_total = $invoice['tax_total'];
        $taxTotal = (new \NumNum\UBL\TaxTotal())->setTaxAmount($tax_total);
        return $taxTotal;
    }

    public function legalEntity($val=null) {
        $name = $this->getBusiness()->legal_registration_name;
        if($val) {
            $name = $val;
        }
        $entity = (new \NumNum\UBL\LegalEntity())->setRegistrationName($name);
        return $entity;
    }

    // Codes list: https://service.unece.org/trade/untdid/d16b/tred/tred4461.htm
    public function paymentMeans() {
        $invoice = $this->getInvoice();
        $code = 97; // Clearing between partners
        $note = $invoice['payment_terms']['terms'] ?? '';

        $codes = [
            10 => 'In cash',
            30 => 'Credit',
            42 => 'Payment to bank account',
            48 => 'Bank card',
            1 => 'Instrument not defined',
        ];

        if($invoice['payment_terms']['terms'] == 'Cash') {
            $code = 10;
            $note = $codes[$code];
        }

        $means = (new \NumNum\UBL\PaymentMeans())
            ->setPaymentMeansCode($code, null)
            //->setInstructionNote($note)
        ;
        return $means;
    }

    public function docAttachment() {
        $inv = new \App\Invoice;
        $inv->setBusiness($this->getBusiness());
        $file_path = $inv->saveInvoicePDF($this->trans_no);

        $attachment = null;
        if($file_path) {
            $attachment = new \NumNum\UBL\Attachment();
            $attachment->setFilePath($file_path);
            $attachment->setExternalReference($this->getDocExReference());
        }
        return $attachment;
    }

    public function plainTextAttachment($content) {
        if($content) {
            $attachment = new \NumNum\UBL\Attachment();
            $attachment->setInlineContent($content);
            return $attachment;
        }
        return null;
    }

    public function additionalDocumentReference($id='') {
        if(!$id) {
            return null;
        }

        $docRef = new \NumNum\UBL\AdditionalDocumentReference();
        $docRef->setId($id);

        if($id == 'ICV') {
            $uuid = \App\Invoice::docRefUUID($this->trans_no);
            $docRef->setUUID($uuid);
        }

        if($id == 'PIH') {
            $previousInvoiceHash = $this->getPreviousInvoiceHash();
            if($previousInvoiceHash) {
                $attachment = $this->plainTextAttachment($previousInvoiceHash);
                $docRef->setAttachment($attachment);
            }
        }

        if($id == 'QR') {
            $qrCode = $this->getQR();
            if($qrCode) {
                $attachment = $this->plainTextAttachment($qrCode);
                $docRef->setAttachment($attachment);
            }
        }

        return $docRef;
    }

    public function partyTaxScheme($companyID=null, $taxSchemeID='VAT') {
        $taxSchemeID = 'VAT';

        $partyTaxScheme = new \NumNum\UBL\PartyTaxScheme();
        if($companyID) {
            $partyTaxScheme->setCompanyId($companyID);
        }

        if($taxSchemeID) {
            $partyTaxScheme->setTaxScheme($this->taxScheme($taxSchemeID));
        }
        
        return $partyTaxScheme;
    }

    public function taxScheme($id='VAT') {
        if($id) {
            return (new \NumNum\UBL\TaxScheme())->setId($id);
        }
        return null;
    }

    // Fix me: tax percent is not correct
    public function itemTaxCategory($item) {
        $taxCatSchemeID = 'VAT';
        $taxScheme = (new \NumNum\UBL\TaxScheme())->setId($taxCatSchemeID);

        // Fix me: find proper solution to specify Zero rated, Exempt Supplies, VAT 
        $classified_id = 'S';

        if($item['tax'] == 0 && stristr($item['tax_type_name'], 'exempt')) {
            $classified_id = 'E';
        }
        if($item['tax'] == 0 && stristr($item['tax_type_name'], 'zero')) {
            $classified_id = 'Z';
        }

        $taxCat = (new \NumNum\UBL\ClassifiedTaxCategory())
            ->setId($classified_id)
            ->setName($item['tax_type_name'])
            ->setPercent($item['tax'])
            ->setTaxScheme($taxScheme);

        return $taxCat;
    }

    // PIH, for first invoice hash of '0'
    public function getPreviousInvoiceHash() {
        $content = '0';
        return base64_encode(hash('sha512', $content));
    }

    // QR code hash
    // Fix me: get real QR code hash
    public function getQR() {
        $content = $this->getBusiness()->id;
        return base64_encode(hash('sha512', $content));
    }

    //SCHEMA: Seller Identification + ”_” + Date + ”T” + Time + ”_” + IRN.xml
    public function fileName($pre='SimpleInvoice') {
        $invoice = $this->getInvoice();
        $business = $this->getBusiness();

        $dt = \Carbon\Carbon::parse($invoice['order_date'])->format('Y-m-d');
        $tm = time();

        $filename = "{$business->trn}_{$dt}T{$tm}_{$invoice['ref']}.xml";
        return $filename;
    }

    public function fileAnchor($filename) {
        return '<a href="'.url('xmls/'.$filename).'" target="_blank">'.$filename.'</a>';
    }

    // Remove new lines from string
    public function clean($str, $max_chars=null) {
        $str = str_replace(PHP_EOL, '', $str);

        if($max_chars && is_numeric($max_chars)) {
            $str = \Str::limit($str, $max_chars);
        }
        return $str;
    }

    public function getDocExReference() {
        return 'ref_'.$this->business->id.'_'.$this->trans_no;
    }
}
