<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class XMLInvoice extends Model
{
    private $schema = 'http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd';
    public static $base_dir = 'xmls';
    public $invoice;
    public $invoiceInDB;
    public $business;
    private $trans_no;
    private $icalc;

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
        return $this;
    }

    public function getBusiness() {
        return $this->business;
    }

    public function setBusiness($business) {
        $this->business = $business;
        return $this;
    }

    public function getInvoice() {
        return $this->invoice;
    }

    public function setInvoice($invoice) {
        $this->invoice = $invoice;
        return $this;
    }

    public function setInvoiceInDB($invoiceInDB) {
        $this->invoiceInDB = $invoiceInDB;
        return $this;
    }

    public function setTypeCode($type) {
        $this->type = $type;
        return $this;
    }

    public function getTypeCode() {
        if($this->type) {
            return $this->type;
        }
        return \App\Invoice::INVOICE;
    }

    public function setCalc() {
        $this->icalc = new Calc;
        if($this->invoice) {
            $this->icalc->setInvoice($this->invoice);
        }

        $taxes = Invoice::getTaxforItems($this->getBusiness());
        if($taxes) {
            $this->icalc->setTaxes($taxes);
        }
    }

    public function getXML() {
        $invoice = $this->getInvoice();
        $this->setCalc();

        $supplierCompany = $this->businessNode();
        $clientCompany = $this->customerNode();
        $invoiceLines = $this->invoiceLines();
        $invoiceTaxes = $this->invoiceTaxes();
        $legalMonetaryTotal = $this->legalMonetaryTotal();
        $paymentMeans = $this->paymentMeans();
        $invoiceTypeCode = $this->getTypeCode();
        $invoiceTypeCodeName = \App\Invoice::getTypeCodeName('01');

        $issue_date = new \DateTime($invoice['order_date']);
        $issue_time = new \DateTime(date('H:i:s'));
        $due_date = new \DateTime($invoice['due_date']);
        $invoice_id = $invoice['ref'];
        $curr_code = $invoice['customer']['curr_code'] ?? 'SAR';
        $curr_code = 'SAR';

        $docReference = (new \NumNum\UBL\ContractDocumentReference())->setId($invoice['ref']);
        $additionalDocRef = $this->additionalDocumentReference();
        $invoice_uuid = $this->invoiceInDB->uuid ?? \App\Invoice::generateUUID();
        /* After receiving error from ZATCA
            "UUID provided in the invoice doesn't match UUID in the provided Request",
            So I set single UUID per all invoices generated, and it didn't show this error again
        */
        $invoice_uuid = env('EGS_UUID'); 

        $documentSignatures = (new \App\Signature())->getDocSignatures();
        $ublExtension = (new \NumNum\UBL\Extensions\UBLExtension())
            ->setExtensionURI('urn:oasis:names:specification:ubl:dsig:enveloped:xades')
            ->setExtensionContent($documentSignatures);
        $extensions = (new \NumNum\UBL\Extensions\UBLExtensions())->addUBLExtension($ublExtension);

        $signature = (new \NumNum\UBL\Signature())
            ->setId('urn:oasis:names:specification:ubl:signature:Invoice')
            ->setSignatureMethod('urn:oasis:names:specification:ubl:dsig:enveloped:xades');

        // Invoice object
        $invoiceXML = new \NumNum\UBL\Invoice();
        $invoiceXML->setId($invoice_id)
            ->setUUID($invoice_uuid)
            ->setInvoiceTypeCode($invoiceTypeCode, $invoiceTypeCodeName)
            ->setIssueDate($issue_date)
            ->setIssueTime($issue_time)
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
            ->setSignature($signature)
            ->setExtensions('SET_UBL_EXTENSIONS_STRING')
            //->setCopyIndicator(false)
            //->setNote($this->clean($invoice['comments']))
        ;

        if($curr_code) {
            $invoiceXML->addTaxTotal($this->onlyTaxTotal());
        }

        if($this->getTypeCode() == Invoice::CREDIT_NOTE) {
            $billingRef = $this->billingReference();
            $invoiceXML->setBillingReference($billingRef);
        }

        $ref_ICV = $this->additionalDocumentReference('ICV');
        $ref_PIH = $this->additionalDocumentReference('PIH');
        $ref_QR = $this->additionalDocumentReference('QR');
        
        $invoiceXML->addAdditionalDocumentReference($ref_ICV);
        $invoiceXML->addAdditionalDocumentReference($ref_PIH);
        $invoiceXML->addAdditionalDocumentReference($ref_QR);

        $allowanceCharge = $this->documentAllowanceCharge();
        if($allowanceCharge) {
            $invoiceXML->setAllowanceCharges($allowanceCharge);
        }

        //$invoiceXML->setExtensions($extensions);
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
        $countrySubentity = $business->country_subentity;
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
            ->setCountrySubentity($countrySubentity)
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
            ->setPostalAddress($supplierAddress)
            ->setLegalEntity($legalEntity)
            ->setPartyTaxScheme($partyTaxScheme);

        $partyIden = (new \NumNum\UBL\PartyIdentification())
            ->setId($identificationID)
            ->setSchemeId($business->identification_scheme);

        if($partyIden) {
            $supplierCompany->setPartyIdentification($partyIden);
        }

        return $supplierCompany;
    }

    public function customerNode() {
        $business = $this->getBusiness();
        $invoice = $this->getInvoice();

        $max_limit = 127;

        $customer_id = $invoice['customer']['id'] ?? 0;
        $customer_name = $invoice['customer']['cust_ref'] ?? $invoice['customer']['debtor_ref'];
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

        // Fix me: get real data for customers
        if($country_iso2 == 'SA') {
            $district = 'ABC Dist';
            $additionalNo = '4574';
            $additionalStreetName = '1004'; // 4 digits only

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

        $idenScheme = 'OTH';
        $partyIden = (new \NumNum\UBL\PartyIdentification())
            ->setId($customer_id)
            ->setSchemeId($idenScheme);

        if($partyIden) {
            $clientCompany->setPartyIdentification($partyIden);
        }

        return $clientCompany;
    }

    public function allowanceTaxCategory($item=null) {
        $taxSchemeID = 'VAT';
        $taxScheme = (new \NumNum\UBL\TaxScheme())->setId($taxSchemeID);
        $classified_id = 'S';
        $taxCategory = new \NumNum\UBL\TaxCategory();
        $def_percent = 15;

        if($item) {
            if($item['tax'] == 0 && stristr($item['tax_type_name'], 'exempt')) {
                $classified_id = 'E';
            }
            if($item['tax'] == 0 && stristr($item['tax_type_name'], 'zero')) {
                $classified_id = 'Z';
            }
            
            $taxCategory->setId($classified_id)
                ->setPercent($item['tax'])
                ->setTaxScheme($taxScheme);
        } else {
            $taxCategory->setId($classified_id)
                ->setPercent($def_percent)
                ->setTaxScheme($taxScheme);
        }

        return $taxCategory;
    }

    public function documentAllowanceCharge() {
        $invoice = $this->getInvoice();
        if(!$invoice) {
            return;
        }

        $documentAllowanceCharge = null;

        foreach($invoice['line_items'] as $item) {
            if($item['discount'] != 0) {
                $documentAllowanceCharge[] = (new \NumNum\UBL\AllowanceCharge())
                    ->setChargeIndicator(false)
                    ->setAllowanceChargeReason('Discount')
                    ->setAmount($this->icalc->getDiscount($item))
                    ->setTaxCategory($this->allowanceTaxCategory($item));
            }
        }

        if($invoice['freight_cost']) {
            $documentAllowanceCharge[] = (new \NumNum\UBL\AllowanceCharge())
                ->setChargeIndicator(false)
                ->setAllowanceChargeReason('Shipping')
                ->setAmount(floatval($invoice['freight_cost']))
                ->setTaxCategory($this->allowanceTaxCategory());
        }

        return $documentAllowanceCharge;
    }

    public function invoiceLines() {
        $invoice = $this->getInvoice();
        $trn = $this->getBusiness()->trn;
        $calc = $this->icalc;

        $invoiceLines = [];
        foreach($invoice['line_items'] as $item) {
            $item_unit = (isset($item['units']) && $item['units']=='each') ? \NumNum\UBL\UnitCode::PIECE : \NumNum\UBL\UnitCode::UNIT;
            $item_unit = 'PCE';
            $lineExtAmount = $calc->calcItemTotal($item);
            $itemTotalTax = $calc->calculateItemTax($item);
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
                ->setTaxAmount($itemTotalTax)
                ->setRoundingAmount($roundingAmount);

            $classifiedTaxCategory = $this->itemTaxCategory($item);

            $allowanceCharge = null;
            if($item['discount'] != 0) {
                $allowanceCharge = (new \NumNum\UBL\AllowanceCharge())
                    ->setChargeIndicator(false)
                    ->setAllowanceChargeReason('discount')
                    ->setAmount($calc->getDiscount($item));
            }

            // Product
            $productItem = (new \NumNum\UBL\Item())
                ->setName($item['stock_id'])
                ->setDescription($item['description'])
                ->setSellersItemIdentification($trn)
                ->setClassifiedTaxCategory($classifiedTaxCategory);

            $invoiceLines[$item['id']] = (new \NumNum\UBL\InvoiceLine())
                ->setId($item['id'])
                ->setItem($productItem)
                ->setInvoicePeriod($invoicePeriod)
                ->setPrice($price)
                ->setTaxTotal($lineTaxTotal)
                ->setUnitCode($item_unit)
                ->setInvoicedQuantity($item['qty_dispatched'])
                ->setLineExtensionAmount($lineExtAmount);

            if($allowanceCharge) {
                $invoiceLines[$item['id']]->setAllowanceCharge($allowanceCharge);
            }
        }

        return $invoiceLines;
    }

    public function totalLineExtensionAmount() {
        $invoice = $this->getInvoice();
        $total = 0;
        foreach($invoice['line_items'] as $item) {
            $total += round($this->icalc->calcItemTotal($item), 2);
        }
        return $total;
    }

    public function legalMonetaryTotal() {
        $invoice = $this->getInvoice();
        $line_ext_amount = $this->totalLineExtensionAmount();
        $payable_amount = $invoice['display_total'];
        $tax_total = $invoice['tax_total'];
        $tax_inclusive_amount = $invoice['display_total'];
        $tax_exclusive_amount = $invoice['display_total'] - $tax_total;
        $allowanceTotal = $this->icalc->allowanceTotal();

        $legalMonetaryTotal = (new \NumNum\UBL\LegalMonetaryTotal())
            ->setLineExtensionAmount($line_ext_amount)
            ->setTaxExclusiveAmount($tax_exclusive_amount)
            ->setTaxInclusiveAmount($tax_inclusive_amount)
            ->setAllowanceTotalAmount($allowanceTotal)
            ->setPayableAmount($payable_amount);

        return $legalMonetaryTotal;
    }

    public function invoiceTaxes() {
        $invoice = $this->getInvoice();
        $taxAmount = $invoice['tax_total'];
        $taxTotal = new \NumNum\UBL\TaxTotal();

        foreach($invoice['taxes'] as $inv_tax) {
            $taxSubTotal = (new \NumNum\UBL\TaxSubTotal())
                ->setTaxCategory($this->getTaxCategory($inv_tax))
                ->setTaxableAmount($inv_tax['net_amount'])
                ->setTaxAmount($inv_tax['amount']);

            $taxTotal->addTaxSubTotal($taxSubTotal);
        }

        $taxTotal->setTaxAmount($taxAmount);
        return $taxTotal;
    }

    // TaxExemptionReasonCode: Code Description from UN/CEFACT code list 5305, D.16B
    // Fix me: add specific reason & reason code
    public function getTaxCategory($invoice_tax) {
        $tname = $invoice_tax['name'];
        $percent = 0;
        $id = 'VAT';
        $taxCategory = new \NumNum\UBL\TaxCategory();

        if($invoice_tax['rate'] == null) {
            $id = 'E';
            $taxCategory->setTaxExemptionReason('Financial services mentioned in Article 29 of the VAT Regulations');
            $taxCategory->setTaxExemptionReasonCode('VATEX-SA-29');
        } else if($invoice_tax['rate'] == 0) {
            $id = 'Z';
            $taxCategory->setTaxExemptionReason('Export of goods');
            $taxCategory->setTaxExemptionReasonCode('VATEX-SA-32');
        } else {
            $id = 'S';
            $percent = $invoice_tax['rate'];
        }

        $taxScheme = (new \NumNum\UBL\TaxScheme())
            ->setId('VAT');

        $taxCategory
            ->setId($id)
            ->setName($tname)
            ->setPercent($percent)
            ->setTaxScheme($taxScheme);

        return $taxCategory;
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

    /**
     * Codes list: https://service.unece.org/trade/untdid/d16b/tred/tred4461.htm
     * CreditNote type: must include cbc:InstructionNote
     * Fix me: For CreditNotes: get proper InstructionNote
     */
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

        $means = new \NumNum\UBL\PaymentMeans();
        $means->setPaymentMeansCode($code, null);
        if($this->type == Invoice::CREDIT_NOTE) {
            $means->setInstructionNote($note);
        }
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

        $tax_percent = $this->icalc->itemTaxRate($item);
        $taxCat = (new \NumNum\UBL\ClassifiedTaxCategory())
            ->setId($classified_id)
            //->setName($item['tax_type_name'])
            ->setPercent($tax_percent)
            ->setTaxScheme($taxScheme);

        return $taxCat;
    }

    // PIH, for first invoice hash of '0'
    // Fix me: get correct PIH
    public function getPreviousInvoiceHash() {
        $content = '0';
        return base64_encode(hash('sha512', $content));
    }

    // QR code hash
    public function getQR() {
        $invoice = $this->getInvoice();

        $qr = new \App\ZatcaQR(
            $invoice['cust_ref'], 
            strval($invoice['customer']['tax_id']), 
            $invoice['order_date'], 
            $invoice['display_total'], 
            $invoice['tax_total']
        );

        $qr->setIsEncoded(false);
        $content = $qr->getQRCode();

        return base64_encode(hash('sha512', $content));
    }

    /**
     * Set CreditNote related SalesInvoice ID as InvoiceDocumentReference ID
     */
    public function billingReference() {
        $invoice_id = \App\Invoice::docRefUUID($this->trans_no);
        $billingRef = (new \NumNum\UBL\BillingReference())->setInvoiceDocumentReferenceID($invoice_id);
        return $billingRef;
    }

    //SCHEMA: Seller Identification + ”_” + Date + ”T” + Time + ”_” + IRN.xml
    public function fileName($pre='SimpleInvoice') {
        $invoice = $this->getInvoice();
        $business = $this->getBusiness();

        $dt = \Carbon\Carbon::parse($invoice['order_date'])->format('Y-m-d');
        $tm = time();
        $invoice_ref = str_replace('/', '_', $invoice['ref']);
        $filename = "{$business->trn}_{$dt}T{$tm}_{$invoice_ref}.xml";

        return $filename;
    }

    public function signedFileName($filename) {
        return str_replace('.xml', '_signed.xml', $filename);
    }

    public function xmlPath($signed=true) {
        $filename = $this->fileName();
        if($signed) {
            $filename = $this->signedFileName($filename);
        }
        return public_path(self::$base_dir) . '/'.$filename;
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
