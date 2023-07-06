# nostr-php

A collection of functions to interact with nostr with php.

## installation

```
composer require utxo-one/nostr-php
```

## usage

```php

use UtxoOne\NostrPhp\Converter;

$converter = new Converter();
$hex = $converter->getHexFromNpub("npub1utx00neqgqln72j22kej3ux7803c2k986henvvha4thuwfkper4s7r50e8");

// returns e2ccf7cf20403f3f2a4a55b328f0de3be38558a7d5f33632fdaaefc726c1c8eb
```
