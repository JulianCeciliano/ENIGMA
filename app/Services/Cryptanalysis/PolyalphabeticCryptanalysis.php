<?php

namespace App\Services\Cryptanalysis;

use App\Services\Ciphers\CipherSupport;

final class PolyalphabeticCryptanalysis
{
    public function __construct(private readonly LanguageAnalyzer $analyzer = new LanguageAnalyzer())
    {
    }

    /**
     * @return array{key: string, plainText: string, score: float, idioma: string}
     */
    public function break(string $cipherText, bool $beaufort = false): array
    {
        $letters = $this->analyzer->lettersOnly($cipherText);

        if ($letters === '') {
            return [
                'key' => '',
                'plainText' => $cipherText,
                'score' => -1_000_000.0,
                'idioma' => 'Espanol',
            ];
        }

        $maximumLength = min(20, max(1, intdiv(strlen($letters), 2)));
        $best = null;

        for ($length = 1; $length <= $maximumLength; $length++) {
            foreach (['Espanol', 'Ingles'] as $language) {
                $key = $this->initialKey($letters, $length, $language, $beaufort);
                $plainText = $this->decrypt($cipherText, $key, $beaufort);
                $score = $this->analyzer->score($plainText, $language);

                for ($pass = 0; $pass < 2; $pass++) {
                    $improved = false;

                    for ($position = 0; $position < $length; $position++) {
                        $positionBest = $key[$position];
                        $positionScore = $score;

                        for ($candidate = 0; $candidate < 26; $candidate++) {
                            $candidateKey = substr_replace($key, chr(ord('A') + $candidate), $position, 1);
                            $candidatePlainText = $this->decrypt($cipherText, $candidateKey, $beaufort);
                            $candidateScore = $this->analyzer->score($candidatePlainText, $language);

                            if ($candidateScore > $positionScore) {
                                $positionBest = chr(ord('A') + $candidate);
                                $positionScore = $candidateScore;
                            }
                        }

                        if ($positionBest !== $key[$position]) {
                            $key = substr_replace($key, $positionBest, $position, 1);
                            $score = $positionScore;
                            $improved = true;
                        }
                    }

                    if (! $improved) {
                        break;
                    }
                }

                $key = $this->reducePeriod($key);
                $plainText = $this->decrypt($cipherText, $key, $beaufort);
                $score = $this->analyzer->score($plainText, $language);

                if ($best === null || $score > $best['score']) {
                    $best = [
                        'key' => $key,
                        'plainText' => $plainText,
                        'score' => $score,
                        'idioma' => $language,
                    ];
                }
            }
        }

        return $best;
    }

    private function decrypt(string $text, string $key, bool $beaufort): string
    {
        if ($key === '') {
            return $text;
        }

        $output = '';
        $position = 0;
        $keyLength = strlen($key);

        foreach (str_split($text) as $character) {
            $index = CipherSupport::letterIndex($character);

            if ($index === null) {
                $output .= $character;
                continue;
            }

            $keyIndex = ord($key[$position % $keyLength]) - ord('A');
            $plainIndex = $beaufort
                ? CipherSupport::mod($keyIndex - $index)
                : CipherSupport::mod($index - $keyIndex);
            $output .= CipherSupport::characterFromIndex($plainIndex, $character);
            $position++;
        }

        return $output;
    }

    private function initialKey(string $letters, int $length, string $language, bool $beaufort): string
    {
        $frequencies = $language === 'Espanol'
            ? [4, 0, 14, 0, 0, 18, 0, 0, 8, 0, 0, 0, 0, 13, 0, 0, 0, 19, 20, 0, 0, 0, 0, 0, 0, 0]
            : [4, 19, 0, 0, 4, 0, 19, 7, 8, 0, 0, 0, 0, 13, 14, 0, 0, 18, 19, 0, 0, 0, 0, 0, 24, 0];
        $key = '';

        for ($position = 0; $position < $length; $position++) {
            $column = '';

            for ($index = $position; $index < strlen($letters); $index += $length) {
                $column .= $letters[$index];
            }

            $bestKey = 0;
            $bestScore = INF;

            for ($candidate = 0; $candidate < 26; $candidate++) {
                $counts = array_fill(0, 26, 0);

                foreach (str_split($column) as $letter) {
                    $cipherIndex = ord($letter) - ord('A');
                    $plainIndex = $beaufort
                        ? CipherSupport::mod($candidate - $cipherIndex)
                        : CipherSupport::mod($cipherIndex - $candidate);
                    $counts[$plainIndex]++;
                }

                $score = 0.0;
                $total = strlen($column);

                if ($total > 0) {
                    foreach ($counts as $index => $count) {
                        $expected = $frequencies[$index] * $total / 100;
                        $score += (($count - $expected) ** 2) / max($expected, 0.01);
                    }
                }

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestKey = $candidate;
                }
            }

            $key .= chr(ord('A') + $bestKey);
        }

        return $key;
    }

    private function reducePeriod(string $key): string
    {
        for ($period = 1; $period <= strlen($key); $period++) {
            if (strlen($key) % $period === 0 && str_repeat(substr($key, 0, $period), intdiv(strlen($key), $period)) === $key) {
                return substr($key, 0, $period);
            }
        }

        return $key;
    }
}
