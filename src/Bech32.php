<?php

namespace UtxoOne\NostrPhp;

class Bech32
{
    public const CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
    public array $ALPHABET;
    public mixed $ENCODING_CONST = 1;

    public function __construct()
    {
        $this->ALPHABET = [];
        for ($z = 0; $z < strlen(self::CHARSET); $z++) {
            $x = self::CHARSET[$z];
            $this->ALPHABET[$x] = $z;
        }
    }

    public function polymodStep($pre)
    {
        $b = $pre >> 25;
        return (((($pre & 0x1ffffff) << 5) ^
            (-(($b >> 0) & 1) & 0x3b6a57b2) ^
            (-(($b >> 1) & 1) & 0x26508e6d) ^
            (-(($b >> 2) & 1) & 0x1ea119fa) ^
            (-(($b >> 3) & 1) & 0x3d4233dd) ^
            (-(($b >> 4) & 1) & 0x2a1462b3)));
    }

    public function prefixChk($prefix)
    {
        $chk = 1;
        for ($i = 0; $i < strlen($prefix); ++$i) {
            $c = ord($prefix[$i]);
            if ($c < 33 || $c > 126) {
                return 'Invalid prefix (' . $prefix . ')';
            }
            $chk = $this->polymodStep($chk) ^ ($c >> 5);
        }
        $chk = $this->polymodStep($chk);
        for ($i = 0; $i < strlen($prefix); ++$i) {
            $v = ord($prefix[$i]);
            $chk = $this->polymodStep($chk) ^ ($v & 0x1f);
        }

        return $chk;
    }

    public function convertBits($data, $inBits, $outBits, $pad)
    {
        $value = 0;
        $bits = 0;
        $maxV = (1 << $outBits) - 1;
        $result = [];
        for ($i = 0; $i < count($data); ++$i) {
            $value = ($value << $inBits) | $data[$i];
            $bits += $inBits;
            while ($bits >= $outBits) {
                $bits -= $outBits;
                $result[] = ($value >> $bits) & $maxV;
            }
        }
        if ($pad) {
            if ($bits > 0) {
                $result[] = ($value << ($outBits - $bits)) & $maxV;
            }
        } else {
            if ($bits >= $inBits) {
                return 'Excess padding';
            }
            if (($value << ($outBits - $bits)) & $maxV) {
                return 'Non-zero padding';
            }
        }
        return $result;
    }

    public function toWords($bytes)
    {
        return $this->convertBits($bytes, 8, 5, true);
    }

    public function fromWordsUnsafe($words)
    {
        $res = $this->convertBits($words, 5, 8, false);
        if (is_array($res)) {
            return $res;
        }
    }

    public function fromWords($words)
    {
        $res = $this->convertBits($words, 5, 8, false);
        if (is_array($res)) {
            return $res;
        }
        throw new \Exception($res);
    }

    public function encode($prefix, $words, $LIMIT = 90)
    {
        if (strlen($prefix) + 7 + count($words) > $LIMIT) {
            throw new \TypeError('Exceeds length limit');
        }

        $prefix = strtolower($prefix);

        // Determine chk mod
        $chk = $this->prefixChk($prefix);
        if (is_string($chk)) {
            throw new \Exception($chk);
        }

        $result = $prefix . '1';
        foreach ($words as $word) {
            if (($word >> 5) !== 0) {
                throw new \Exception('Non 5-bit word');
            }
            $chk = $this->polymodStep($chk) ^ $word;
            $result .= $this->ALPHABET[$word];
        }

        for ($i = 0; $i < 6; ++$i) {
            $chk = $this->polymodStep($chk);
        }

        $chk ^= $this->ENCODING_CONST;
        for ($i = 0; $i < 6; ++$i) {
            $v = ($chk >> ((5 - $i) * 5)) & 0x1f;
            $result .= $this->ALPHABET[$v];
        }

        return $result;
    }

    public function decode($str, $LIMIT = 90)
    {
        $LIMIT = $LIMIT ?: 90;

        if (strlen($str) < 8) {
            return $str . ' too short';
        }
        if (strlen($str) > $LIMIT) {
            return 'Exceeds length limit';
        }

        // Don't allow mixed case
        $lowered = strtolower($str);
        $uppered = strtoupper($str);
        if ($str !== $lowered && $str !== $uppered) {
            return 'Mixed-case string ' . $str;
        }

        $str = $lowered;
        $split = strrpos($str, '1');
        if ($split === false) {
            return 'No separator character for ' . $str;
        }
        if ($split === 0) {
            return 'Missing prefix for ' . $str;
        }

        $prefix = substr($str, 0, $split);
        $wordChars = substr($str, $split + 1);

        if (strlen($wordChars) < 6) {
            return 'Data too short';
        }

        $chk = $this->prefixChk($prefix);
        if (is_string($chk)) {
            return $chk;
        }

        $words = [];
        for ($i = 0; $i < strlen($wordChars); ++$i) {
            $c = $wordChars[$i];
            $v = $this->ALPHABET[$c];
            if ($v === null) {
                return 'Unknown character ' . $c;
            }
            $chk = $this->polymodStep($chk) ^ $v;

            // Not in the checksum?
            if ($i + 6 >= strlen($wordChars)) {
                continue;
            }

            $words[] = $v;
        }

        if ($chk !== $this->ENCODING_CONST) {
            return 'Invalid checksum for ' . $str;
        }

        return ['prefix' => $prefix, 'words' => $words];
    }

}
