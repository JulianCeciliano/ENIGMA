<?php

declare(strict_types=1);

use App\Services\CipherService;

test('encrypts the documented transformation vectors', function (
    string $algorithm,
    string $text,
    array $parameters,
    string $expected,
): void {
    $result = (new CipherService)->transform($algorithm, 'encrypt', $text, $parameters, false);

    expect($result['result'])->toBe($expected)
        ->and($result['steps'])->toBe([]);
})->with([
    'atbash' => ['atbash', 'ABC xyz!', [], 'ZYX cba!'],
    'caesar' => ['caesar', 'HELLO', ['shift' => 3], 'KHOOR'],
    'rot13' => ['rot13', 'Hello, World!', [], 'Uryyb, Jbeyq!'],
    'substitution' => [
        'substitution',
        'ABC xyz',
        ['key' => 'QWERTYUIOPASDFGHJKLZXCVBNM'],
        'QWE bnm',
    ],
    'affine' => ['affine', 'AFFINE', ['a' => 5, 'b' => 8], 'IHHWVC'],
    'vigenere' => ['vigenere', 'ATTACKATDAWN', ['key' => 'LEMON'], 'LXFOPVEFRNHR'],
    'beaufort' => ['beaufort', 'HELLO', ['key' => 'FORT'], 'YKGIR'],
    'rail fence' => [
        'rail-fence',
        'WEAREDISCOVEREDFLEEATONCE',
        ['rails' => 3],
        'WECRLTEERDSOEEFEAOCAIVDEN',
    ],
]);

test('all ten ciphers round trip ASCII, punctuation, and UTF-8 text', function (): void {
    $service = new CipherService;
    $text = "Ataque al amanecer! 123\nÑandú.";
    $cases = [
        ['atbash', []],
        ['caesar', ['shift' => -7]],
        ['rot13', []],
        ['substitution', ['key' => 'QWERTYUIOPASDFGHJKLZXCVBNM']],
        ['affine', ['a' => 21, 'b' => 17]],
        ['vigenere', ['key' => 'CLAVE']],
        ['beaufort', ['key' => 'FORT']],
        ['autokey', ['key' => 'QUEEN']],
        ['rail-fence', ['rails' => 4]],
        ['columnar', ['key' => 'ZEBRAS']],
    ];

    foreach ($cases as [$algorithm, $parameters]) {
        $encrypted = $service->transform($algorithm, 'encrypt', $text, $parameters, false)['result'];
        $decrypted = $service->transform($algorithm, 'decrypt', $encrypted, $parameters, false)['result'];

        expect($decrypted)->toBe($text);
    }
});

test('columnar transposition preserves length and stable duplicate ordering', function (): void {
    $service = new CipherService;
    $text = 'WE ARE DISCOVERED!';
    $parameters = ['key' => 'BALLOON'];

    $encrypted = $service->transform('columnar', 'encrypt', $text, $parameters, false)['result'];
    $decrypted = $service->transform('columnar', 'decrypt', $encrypted, $parameters, false)['result'];

    expect(strlen($encrypted))->toBe(strlen($text))
        ->and($decrypted)->toBe($text);
});

test('catalog contains the ten documented strategies', function (): void {
    $catalog = (new CipherService)->catalog();
    $ids = array_column($catalog, 'id');

    expect($catalog)->toHaveCount(10)
        ->and($ids)->toContain('atbash')
        ->and($ids)->toContain('autokey')
        ->and($ids)->toContain('columnar');
});

test('steps honor the educational limits and alphabet mappings', function (): void {
    $service = new CipherService;
    $text = str_repeat('A ', 200);

    $flow = $service->transform('vigenere', 'encrypt', $text, ['key' => 'LEMON'], true);
    $mapping = $service->transform('atbash', 'encrypt', 'ABC', [], true);

    expect($flow['steps'][0]['rows'])->toHaveCount(160)
        ->and($mapping['steps'][0]['rows'])->toHaveCount(26);
});

test('vigenere steps use the same key position as the result', function (): void {
    $result = (new CipherService)->transform(
        'vigenere',
        'encrypt',
        'ATTACK',
        ['key' => 'LEMON'],
        true,
    );

    expect(array_column($result['steps'][0]['rows'], 3))
        ->toBe(['L', 'X', 'F', 'O', 'P', 'V']);
});

test('rail fence steps count UTF-8 characters instead of bytes', function (): void {
    $result = (new CipherService)->transform(
        'rail-fence',
        'encrypt',
        'Ñandú',
        ['rails' => 2],
        true,
    );

    expect($result['steps'][0]['rows'])->toHaveCount(5);
});

test('fixed breaks recover deterministic transformations', function (): void {
    $service = new CipherService;
    $text = str_repeat(
        'THE CRYPTOGRAPHY CLASS EXPLAINS WHY HISTORICAL CIPHERS ARE WEAK. ',
        3,
    );

    $atbashCipherText = $service->transform('atbash', 'encrypt', $text, [], false)['result'];
    $rot13CipherText = $service->transform('rot13', 'encrypt', $text, [], false)['result'];

    expect($service->breakCipher('atbash', $atbashCipherText)['plainText'])->toBe($text)
        ->and($service->breakCipher('rot13', $rot13CipherText)['plainText'])->toBe($text);
});

test('polyalphabetic breaks recover keys from long educational samples', function (): void {
    $service = new CipherService;
    $text = str_repeat(
        'THE CRYPTOGRAPHY CLASS EXPLAINS WHY HISTORICAL CIPHERS ARE WEAK. THE MESSAGE HIDES REPEATED LETTERS. ',
        3,
    );

    $vigenere = $service->transform('vigenere', 'encrypt', $text, ['key' => 'LEMON'], false)['result'];
    $beaufort = $service->transform('beaufort', 'encrypt', $text, ['key' => 'FORT'], false)['result'];
    $autokey = $service->transform('autokey', 'encrypt', $text, ['key' => 'QUEEN'], false)['result'];

    expect($service->breakCipher('vigenere', $vigenere)['recoveredKey'])->toBe('LEMON')
        ->and($service->breakCipher('beaufort', $beaufort)['recoveredKey'])->toBe('FORT')
        ->and($service->breakCipher('autokey', $autokey)['recoveredKey'])->toBe('QUEEN');
})->group('slow');
