<?php

use PHPUnit\Framework\TestCase;
use UtxoOne\NostrPhp\Converter;

final class ConverterTest extends TestCase
{
    public function testGetHexFromNpub(): void
    {
        $converter = new Converter();

        $npub = "npub1utx00neqgqln72j22kej3ux7803c2k986henvvha4thuwfkper4s7r50e8";
        $expectedHex = "e2ccf7cf20403f3f2a4a55b328f0de3be38558a7d5f33632fdaaefc726c1c8eb";

        $hex = $converter->getHexFromNpub($npub);

        $this->assertEquals($expectedHex, $hex);
    }
}
