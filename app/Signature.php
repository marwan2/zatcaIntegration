<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
	private $invoice;

    public function getInvoice() {
        return $this->invoice;
    }

    public function setInvoice($invoice): Calc {
        $this->invoice = $invoice;
        return $this;
    }

    public function getSignatureInfo()
    {
        $signature = new \NumNum\UBL\Extensions\SignatureInformation();
        $signature->setId('urn:oasis:names:specification:ubl:signature:1');
        $signature->setReferencedSignatureID('urn:oasis:names:specification:ubl:signature:Invoice');
        $signature->setSignature('Signature');

        return $signature;
    }

    public function getDocSignatures() {
        $extContent = new \NumNum\UBL\Extensions\ExtensionContent();
        $extContent->setSignatureInfo($this->getSignatureInfo());
        return $extContent;
    }
}
