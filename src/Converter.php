<?php

namespace UtxoOne\NostrPhp;

use UtxoOne\NostrPhp\Bech32;

class Converter
{
    public function getHexFromNpub(string $npub): string
    {
        $bech32 = new Bech32();

        $decoded = $bech32->decode($npub);
        $bytes = $bech32->fromWords($decoded['words']);
        return $this->hexEncode($bytes);
    }

    private function hexChar($val)
    {
        if ($val < 10) {
            return chr(48 + $val);
        }
        if ($val < 16) {
            return chr(97 + $val - 10);
        }
    }

    private function hexEncode($buf)
    {
        $str = "";
        for ($i = 0; $i < count($buf); $i++) {
            $c = $buf[$i];
            $str .= $this->hexChar($c >> 4);
            $str .= $this->hexChar($c & 0xF);
        }
        return $str;
    }
}
