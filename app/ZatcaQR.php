<?php

namespace App;

use API\Helpers\Helper;
use App\McledgerQrCode;

class ZatcaQR {

    private $seller_name, $tax_number, $date, $total_amount, $tax_amount;

    public function __construct(string $seller_name, string $tax_number, string $date, $total_amount, $tax_amount)
    {
        $this->seller_name = $seller_name;
        $this->tax_number = $tax_number;
        $this->date = date(DATE_ISO8601, strtotime($date));
        $this->total_amount = $total_amount;
        $this->tax_amount = $tax_amount;
    }

    private function generateQRCode()
    {
        $QRCodeAsBase64 = McledgerQrCode::fromArray([
            $this->seller_name, // seller name        
            $this->tax_number, // seller tax number
            $this->date, // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
            $this->total_amount, // invoice total amount
            $this->tax_amount // invoice tax amount
        ])->render();

        return $QRCodeAsBase64;
    }

    public function getQRCode()
    {
        $generatedQRCode = $this->generateQRCode();
        return $generatedQRCode;
    }

    public function renderQRCodeImg()
    {
        $srcQRCode = $this->getQRCode();
        echo "<img src='$srcQRCode' alt=''/>";
    }
}
