<?php

namespace App;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use InvalidArgumentException;

class McledgerQrCode
{
    protected $data = [];

    private function __construct($data)
    {
        $this->data = array_filter($data, function ($tag) {
            return $tag;
        });

        if (\count($this->data) === 0) {
            throw new InvalidArgumentException('malformed data structure');
        }
    }

    public static function fromArray(array $data): McledgerQrCode
    {
        return new self($data);
    }

    /**
     * Encodes an TLV data structure.
     *
     * @return string Returns a string representing the encoded TLV data structure.
     */
    public function toTLV(): string
    {
        return implode('', array_map(function ($tag) {
            return (string) $tag;
        }, $this->data));
    }

    /**
     * Encodes an TLV as base64
     *
     * @return string Returns the TLV as base64 encode.
     */
    public function toBase64(): string
    {
        return base64_encode($this->toTLV());
    }

    /**
     * Render the QR code as base64 data image.
     *
     * @return string
     */
    public function render($scale = 5, $base64 = true): string
    {
        $options = new QROptions([
              'scale' => $scale,
            ]
        );

        if($base64) {
            return (new QRCode($options))->render($this->toBase64());
        }
        return (new QRCode($options))->render($this->toTLV());
    }
}
