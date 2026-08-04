<?php

namespace App\Services;

use App\Services\Ciphers\AffineCipher;
use App\Services\Ciphers\AtbashCipher;
use App\Services\Ciphers\AutokeyCipher;
use App\Services\Ciphers\BeaufortCipher;
use App\Services\Ciphers\CaesarCipher;
use App\Services\Ciphers\ClassicCipher;
use App\Services\Ciphers\ColumnarTranspositionCipher;
use App\Services\Ciphers\MonoalphabeticSubstitutionCipher;
use App\Services\Ciphers\RailFenceCipher;
use App\Services\Ciphers\Rot13Cipher;
use App\Services\Ciphers\VigenereCipher;
use InvalidArgumentException;

final class CipherService
{
    /**
     * @var array<string, ClassicCipher>
     */
    private array $ciphers;

    public function __construct()
    {
        $ciphers = [
            new AtbashCipher(),
            new CaesarCipher(),
            new Rot13Cipher(),
            new MonoalphabeticSubstitutionCipher(),
            new AffineCipher(),
            new VigenereCipher(),
            new BeaufortCipher(),
            new AutokeyCipher(),
            new RailFenceCipher(),
            new ColumnarTranspositionCipher(),
        ];

        $this->ciphers = [];

        foreach ($ciphers as $cipher) {
            $this->ciphers[$cipher->id()] = $cipher;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function catalog(): array
    {
        return array_map(
            static fn (ClassicCipher $cipher): array => $cipher->metadata(),
            array_values($this->ciphers),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function breakCipher(string $algorithm, string $cipherText): array
    {
        $cipher = $this->find($algorithm);

        return [
            'algorithm' => $cipher->id(),
            'algorithmName' => $cipher->metadata()['name'],
            ...$cipher->breakCipher($cipherText),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function transform(
        string $algorithm,
        string $operation,
        string $text,
        array $parameters,
        bool $includeSteps = true,
    ): array {
        $cipher = $this->find($algorithm);
        $normalizedOperation = strtolower(trim($operation));

        $result = match ($normalizedOperation) {
            'encrypt', 'cifrar' => $cipher->encrypt($text, $parameters, $includeSteps),
            'decrypt', 'descifrar' => $cipher->decrypt($text, $parameters, $includeSteps),
            default => throw new InvalidArgumentException('Operación no válida.'),
        };

        return [
            'algorithm' => $cipher->id(),
            'algorithmName' => $cipher->metadata()['name'],
            'operation' => in_array($normalizedOperation, ['encrypt', 'cifrar'], true) ? 'encrypt' : 'decrypt',
            ...$result,
        ];
    }

    private function find(string $algorithm): ClassicCipher
    {
        $id = strtolower(trim($algorithm));

        if (! isset($this->ciphers[$id])) {
            throw new InvalidArgumentException('Algoritmo no existente.');
        }

        return $this->ciphers[$id];
    }
}
