<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Storage;

class Signing extends Model
{
    public $business;

    private $template = '<xades:SignedProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="xadesSignedProperties">
        <xades:SignedSignatureProperties>
            <xades:SigningTime>SET_SIGN_TIMESTAMP</xades:SigningTime>
            <xades:SigningCertificate>
                <xades:Cert>
                    <xades:CertDigest>
                        <ds:DigestMethod xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                        <ds:DigestValue xmlns:ds="http://www.w3.org/2000/09/xmldsig#">SET_CERTIFICATE_HASH</ds:DigestValue>
                    </xades:CertDigest>
                    <xades:IssuerSerial>
                        <ds:X509IssuerName xmlns:ds="http://www.w3.org/2000/09/xmldsig#">SET_CERTIFICATE_ISSUER</ds:X509IssuerName>
                        <ds:X509SerialNumber xmlns:ds="http://www.w3.org/2000/09/xmldsig#">SET_CERTIFICATE_SERIAL_NUMBER</ds:X509SerialNumber>
                    </xades:IssuerSerial>
                </xades:Cert>
            </xades:SigningCertificate>
        </xades:SignedSignatureProperties>
    </xades:SignedProperties>';

    private $template_after_signing = '
                            <xades:SignedProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="xadesSignedProperties">
                                <xades:SignedSignatureProperties>
                                    <xades:SigningTime>SET_SIGN_TIMESTAMP</xades:SigningTime>
                                    <xades:SigningCertificate>
                                        <xades:Cert>
                                            <xades:CertDigest>
                                                <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"></ds:DigestMethod>
                                                <ds:DigestValue>SET_CERTIFICATE_HASH</ds:DigestValue>
                                            </xades:CertDigest>
                                            <xades:IssuerSerial>
                                                <ds:X509IssuerName>SET_CERTIFICATE_ISSUER</ds:X509IssuerName>
                                                <ds:X509SerialNumber>SET_CERTIFICATE_SERIAL_NUMBER</ds:X509SerialNumber>
                                            </xades:IssuerSerial>
                                        </xades:Cert>
                                    </xades:SigningCertificate>
                                </xades:SignedSignatureProperties>
                            </xades:SignedProperties>';

    public function setBusiness($business) {
        $this->business = $business;
    }

    /**
     * Array of:
        $sign_timestamp, 
        $certificate_hash, 
        $certificate_issuer, 
        $certificate_serial_number
     */ 
    public function defaultUBLExtensionsSignedPropertiesForSigning($properties) {
        extract($properties);

        $template_content = trim($this->template);
        $template_content = str_replace("SET_SIGN_TIMESTAMP", $sign_timestamp, $template_content);
        $template_content = str_replace("SET_CERTIFICATE_HASH", $certificate_hash, $template_content);
        $template_content = str_replace("SET_CERTIFICATE_ISSUER", $certificate_issuer, $template_content);
        $template_content = str_replace("SET_CERTIFICATE_SERIAL_NUMBER", $certificate_serial_number, $template_content);
        return $template_content;
    }

    /**
     * Array of:
        $sign_timestamp, 
        $certificate_hash, 
        $certificate_issuer, 
        $certificate_serial_number
     */
    public function populateUBLExtensionsSignedProperties($properties) {
        extract($properties);

        $template_content = trim($this->template_after_signing);
        $template_content = str_replace("SET_SIGN_TIMESTAMP", $sign_timestamp, $template_content);
        $template_content = str_replace("SET_CERTIFICATE_HASH", $certificate_hash, $template_content);
        $template_content = str_replace("SET_CERTIFICATE_ISSUER", $certificate_issuer, $template_content);
        $template_content = str_replace("SET_CERTIFICATE_SERIAL_NUMBER", $certificate_serial_number, $template_content);
        return $template_content;
    }

    public function getCertificateString($production=false) {
        if(!$this->business) {
            throw new \Exception("Business required to get its certificates");
        }

        $production_certificate = $this->business->xprefix.'_compliance_cert.pem';
        $production_certificate = $this->business->xprefix.'_production_cert.pem';
        $certificate = null;

        if($production) {
            if(Storage::exists($production_certificate)) {
                $certificate = Storage::get($production_certificate);
            }
        } else {
            if(Storage::exists($production_certificate)) {
                $certificate = Storage::get($production_certificate);
            }
        }

        return $certificate;
    }

    public function getPrivateKey() {
        $privkey_file = 'privatekey.pem';
        $private_key = null;
        if(Storage::exists($privkey_file)) {
            $private_key = Storage::get($privkey_file);
        }
        return $private_key;
    }

    public function signInvoice($invoice_xml, $production=false) {
        $certificate = $this->getCertificateString($production);
        $private_key = $this->getPrivateKey();

        if (!$certificate || !$private_key) {
            throw new \Exception("EGS is missing a certificate/private key to sign the invoice.");
        }

        return $this->sign($invoice_xml, $certificate, $private_key);
    }

    /**
     * Signs the invoice.
     * @param invoice_xml Invoice XML string.
     * @param certificate_string String signed EC certificate.
     * @param private_key_string String ec-secp256k1 private key;
     * @returns String signed invoice xml, includes QR generation.
     */
    public function sign($invoice_xml, $certificate_string, $private_key_string) {
        return $this->generateSignedXMLString($invoice_xml, $certificate_string, $private_key_string);
    }

    /**
     * Main signing function.
     * @param invoice_xml XMLDocument of invoice to be signed.
     * @param certificate_string String signed EC certificate.
     * @param private_key_string String ec-secp256k1 private key;
     * @return signed_invoice_string: string, invoice_hash: string, qr: string
     */
    public function generateSignedXMLString($invoice_xml, $certificate_string, $private_key_string) {

        // 1: Invoice Hash
        $invoice_hash = $this->getInvoiceHash($invoice_xml);
        
        // 2: Certificate hash and certificate info
        $cert_info = $this->getCertificateInfo($certificate_string);

        // 3: Digital Certificate
        $digital_signature = $this->createInvoiceDigitalSignature($invoice_hash, $private_key_string);

        // 4: QR
        $qr = $this->generateQR([
            $invoice_xml,
            'digital_signature' => $digital_signature,
            'public_key' => $cert_info['public_key'],
            'certificate_signature' => $digital_signature,
        ]);

        // Set Signed properties
        // sign_timestamp: 2022-09-20T10:56:15Z
        $signed_properties_props = [
            'sign_timestamp' => \Carbon\Carbon::now()->toRfc3339String(),
            'certificate_hash' => $cert_info['hash'],
            'certificate_issuer' => $cert_info['issuer'],
            'certificate_serial_number' => $cert_info['serial_number']
        ];
        
        $ubl_signature_signed_properties_xml_string_for_signing = $this->defaultUBLExtensionsSignedPropertiesForSigning($signed_properties_props);
        $ubl_signature_signed_properties_xml_string = $this->populateUBLExtensionsSignedProperties($signed_properties_props);

        // 5: Get SignedProperties hash
        $signed_properties_hash = hash("sha256", $ubl_signature_signed_properties_xml_string_for_signing, false);
        $signed_properties_hash = base64_encode($signed_properties_hash);

        // UBL Extensions
        $ublExtension = new \App\UBLSigningExtension;
        $ubl_signature_xml_string = $ublExtension->populate(
            $invoice_hash,
            $signed_properties_hash,
            $digital_signature,
            $this->cleanUpCertificateString($certificate_string),
            $ubl_signature_signed_properties_xml_string
        );

        // Set signing elements
        $unsigned_invoice_str = $invoice_xml;
        $unsigned_invoice_str = str_replace("SET_UBL_EXTENSIONS_STRING", $ubl_signature_xml_string, $unsigned_invoice_str);
        $unsigned_invoice_str = str_replace("SET_QR_CODE_DATA", $qr, $unsigned_invoice_str);
        $signed_invoice_string = $unsigned_invoice_str;

        $doc = new \DOMDocument;
        $doc->loadXML($signed_invoice_string);
        $signed_invoice_string = $doc->C14N(false, false);

        return list($signed_invoice_string, $invoice_hash, $qr) = [$signed_invoice_string, $invoice_hash, $qr];
    }

    /**
     * Removes (UBLExtensions (Signing), Signature Envelope, and QR data) Elements. Then canonicalizes the XML to c14n.
     * In Order to prep for hashing.
     * @param invoice_xml XMLDocument.
     * @return purified Invoice XML string.
     */
    public function getPureInvoiceString($invoice_xml) {
        $doc = new \DOMDocument; 
        $doc->loadXML($invoice_xml);

        // Remove UBLExtensions Node
        $UBLExtensions = $doc->getElementsByTagName('UBLExtensions');
        $totalMatches = $UBLExtensions->length;
        $elementsToDelete = array();
        for ($i = 0; $i < $totalMatches; $i++){
            $elementsToDelete[] = $UBLExtensions->item($i);
        }

        foreach ($elementsToDelete as $elementToDelete ) {
            $elementToDelete->parentNode->removeChild($elementToDelete);
        }

        // Remove Signature Node
        $SignatureElement = $doc->getElementsByTagName('Signature');
        $totalMatches = $SignatureElement->length;
        $elementsToDelete = array();
        for ($i = 0; $i < $totalMatches; $i++){
            $elementsToDelete[] = $SignatureElement->item($i);
        }

        foreach ($elementsToDelete as $elementToDelete ) {
            $elementToDelete->parentNode->removeChild($elementToDelete);
        }

        // Remove QR Node
        $docRefs = $doc->getElementsByTagName('AdditionalDocumentReference');
        foreach($docRefs as $docRef) {
            $ref_id = $docRef->getElementsByTagName('ID');
            foreach($ref_id as $id) {
                if($id->nodeValue == 'QR') {
                    $docRef->parentNode->removeChild($docRef);
                }
            }
        }

        // Canonicalizes the XML
        $xml_string = $doc->C14N(false, false);
        return $xml_string;
    }

    public function getInvoiceHash($invoice_xml) {
        $pure_invoice_string = $this->getPureInvoiceString($invoice_xml);
        $hashed_str = hash("sha256", $pure_invoice_string, true); // true: indicates generate output as binary
        return base64_encode($hashed_str);
    }

    /**
     * Removes header and footer from certificate string.
     * @param certificate_string.
     * @return String base64 encoded certificate body.
     */
    public function cleanUpCertificateString($str) {
        $str = str_replace('-----BEGIN CERTIFICATE-----', '', $str);
        $str = str_replace('-----END CERTIFICATE-----', '', $str);
        $str = str_replace('\r\n', '', $str);
        $searches = array("\r", "\n", "\r\n");

        // Replace the line breaks with a space
        $string = str_replace($searches, "", $str);

        // Replace multiple spaces with one
        $output = preg_replace('!\s+!', '', trim($string));
        return $output;
    }

    public function getCertificateHash ($certificate_string) {
        $certificate_hash = hash('sha256', $certificate_string, false);
        $certificate_hash = base64_encode($certificate_hash);
        return $certificate_hash;
    }

    /**
     * Gets certificate hash, x509IssuerName, and X509SerialNumber and formats them according to ZATCA.
     * @param certificate_string String base64 encoded certificate body.
     * @return {hash: string, issuer: string, serial_number: string, public_key: Buffer, signature: Buffer}.
     */
    public function getCertificateInfo($certificate_string) {
        $cleanedup_certificate_string = $this->cleanUpCertificateString($certificate_string);
        $wrapped_certificate_string = "-----BEGIN CERTIFICATE-----\n{$cleanedup_certificate_string}\n-----END CERTIFICATE-----";

        $hash = $this->getCertificateHash($cleanedup_certificate_string);
        $x509 = openssl_x509_parse($wrapped_certificate_string);

        $cert = $wrapped_certificate_string;

        $pkey_details = openssl_pkey_get_details(openssl_pkey_get_public($wrapped_certificate_string));
        $pkey_hex = bin2hex($pkey_details['key']);

        return [
            'hash' => $hash,
            'issuer' => $x509['issuer']['CN'] ?? '',
            'serial_number' => $x509['serialNumber'] ?? '',
            'public_key' => $pkey_details['key'] ?? '',
            'signature' => $pkey_details['ec']['curve_oid'] ?? '',
            'pkey_hex' => $pkey_hex,
            'x509' => $x509,
        ];
    }

    /**
     * Removes header and footer from private key string.
     * @param $str: privatek_key_string ec-secp256k1 private key string.
     * @return String base64 encoded private key body.
     */
     public function cleanUpPrivateKeyString ($str) {
        $str = str_replace('-----BEGIN EC PRIVATE KEY-----', '', $str);
        $str = str_replace('-----END EC PRIVATE KEY-----', '', $str);
        $str = str_replace('\r\n', '', $str);
        $searches = array("\r", "\n", "\r\n");
        $string = str_replace($searches, "", $str);
        $output = preg_replace('!\s+!', '', trim($string));
        return $output;
    }

    /**
     * Creates invoice digital signature according to ZATCA.
     * https://zatca.gov.sa/ar/E-Invoicing/SystemsDevelopers/Documents/20220624_ZATCA_Electronic_Invoice_Security_Features_Implementation_Standards.pdf
     * 1.4: Digital signature, part of the cryptographic stamp (invoice hash signed using private key) (BS: KSA-15).
     * @param invoice_hash String base64 encoded invoice hash.
     * @param private_key_string String base64 encoded ec-secp256k1 private key body.
     * @return String base64 encoded digital signature.
     */
    public function createInvoiceDigitalSignature($invoice_hash, $private_key_string=null) {
        if(!$private_key_string) {
            $private_key_string = $this->getPrivateKey();
        }

        if(!$private_key_string) {
            throw new \Exception('Private key is missing for invoice digital signature');
        }

        $invoice_hash_bytes = base64_decode($invoice_hash);
        $cleanedup_private_key_string = $this->cleanUpPrivateKeyString($private_key_string);
        $wrapped_private_key_string = "-----BEGIN EC PRIVATE KEY-----\n{$cleanedup_private_key_string}\n-----END EC PRIVATE KEY-----";

        // Create signature
        openssl_sign($invoice_hash_bytes, $signature, $wrapped_private_key_string, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    /**
     * Generate QR code 
     */
    public function generateQR($properties = []) {
        return 'Get QR code from ZatcaQR php Class';
    }
}