<?php

namespace App\Services\Cryptanalysis;

use Normalizer;

final class LanguageAnalyzer
{
    /**
     * @var array<string, array{frequencies: array<int, float>, bigrams: array<string, float>, trigrams: array<string, float>, words: array<string, bool>}>
     */
    private array $profiles = [];

    private const ENGLISH_LETTERS = [
        0.08167, 0.01492, 0.02782, 0.04253, 0.12702, 0.02228, 0.02015,
        0.06094, 0.06966, 0.00153, 0.00772, 0.04025, 0.02406, 0.06749,
        0.07507, 0.01929, 0.00095, 0.05987, 0.06327, 0.09056, 0.02758,
        0.00978, 0.02360, 0.00150, 0.01974, 0.00074,
    ];

    private const SPANISH_LETTERS = [
        0.1253, 0.0142, 0.0468, 0.0586, 0.1368, 0.0069, 0.0101,
        0.0070, 0.0625, 0.0044, 0.0002, 0.0497, 0.0315, 0.0671,
        0.0868, 0.0251, 0.0088, 0.0687, 0.0798, 0.0463, 0.0393,
        0.0090, 0.0001, 0.0022, 0.0090, 0.0052,
    ];

    private const ENGLISH_BIGRAMS = 'TH HE IN ER AN RE ON AT EN ND TI ES OR TE OF ED IS IT AL AR ST TO NT NG SE HA AS OU IO LE VE CO ME DE HI RI RO IC NE EA RA CE LI CH LL BE MA SI OM UR';

    private const ENGLISH_TRIGRAMS = 'THE AND ING HER ERE ENT THA NTH WAS ETH FOR DTH HES VER HIS OFT STH OTH RES TER EST ERS ATI HAT ATE ALL EVE NOT ITH TIO ONE OUR OUT ARE BUT HAD WIT THI TED ION MEN CON PRO COM YOU WAS DEL ENT OFT ANY';

    private const ENGLISH_WORDS = 'A AN AND ARE AS AT BE BUT BY CAN FOR FROM HE HER HIS I IF IN IS IT ME MY NO NOT OF ON OR OUR SHE THE THEY THIS TO WAS WE WERE WHAT WHEN WHERE WHO WITH YOU YOUR TEXT KEY MESSAGE ATTACK DAWN WORLD CRYPTOGRAPHY CLASS EXPLAINS WHY HISTORICAL CIPHERS WEAK REPEATED HIDES LETTERS SHORT TIME LANGUAGE PATTERNS REVEAL REVEALS LONG ENOUGH HISTORIC PATTERN';

    private const SPANISH_BIGRAMS = 'DE LA EL EN ES ER RE AR AS AL LO RA TA OS TE SE OR ON AD AN CO CI CA QU UE NT IA AC DO TO EC MI NO MA PA PR TR BR PL CR GR LL CH CE SI ME DI UN SU IO';

    private const SPANISH_TRIGRAMS = 'QUE LOS LAS DEL POR CON UNA EST ARA ENT ION CIO ONA ADO ALA NTE RES ERO PER PAR SOB TRA PRO COM MEN TOR CUA MAR TIE ACI ESE ESO EST IST ANT EMO ERA DES VER ALL ELL ILL ELL UND UNO QUI IEN MAS SIN';

    private const SPANISH_WORDS = 'A AL DE DEL EL ELLA EN ES ESTA ESTE HAY LA LAS LO LOS ME NO PARA POR QUE SE SI SON SU SUS UN UNA UNO Y CON COMO MUNDO TEXTO CLAVE MENSAJE ATAQUE AMANECER ANALISIS FRECUENCIA CIFRADO CIFRADOS DEMUESTRA DEBIL DEBILES LLAVE LETRAS TIEMPO CORTO PATRONES REVELAN CUANDO LARGO SUFICIENTE CLASE EXPLICA PORQUE HISTORICO HISTORICA';

    /**
     * @return array{score: float, idioma: string}
     */
    public function analyze(string $text): array
    {
        $spanish = $this->score($text, 'Español');
        $english = $this->score($text, 'Inglés');

        return $spanish >= $english
            ? ['score' => $spanish, 'idioma' => 'Español']
            : ['score' => $english, 'idioma' => 'Inglés'];
    }

    public function score(string $text, string $language): float
    {
        $letters = $this->lettersOnly($text);

        if ($letters === '') {
            return -1_000_000.0;
        }

        $profile = $this->profile($language);
        $frequencies = $profile['frequencies'];
        $counts = array_fill(0, 26, 0);
        $length = strlen($letters);

        foreach (str_split($letters) as $letter) {
            $counts[ord($letter) - ord('A')]++;
        }

        $letterLogLikelihood = 0.0;
        $chiSquared = 0.0;

        foreach ($counts as $index => $count) {
            $expected = $frequencies[$index] * $length;
            $letterLogLikelihood += $count * log(max($frequencies[$index], 0.000001));
            $chiSquared += (($count - $expected) ** 2) / max($expected, 0.000001);
        }

        $letterLogLikelihood /= $length;
        $chiSquared /= $length;

        $bigrams = $this->ngramScore($letters, 2, $profile['bigrams']);
        $trigrams = $this->ngramScore($letters, 3, $profile['trigrams']);
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $knownWords = 0;
        $known = $profile['words'];

        foreach ($words as $word) {
            $normalized = $this->lettersOnly($word);

            if ($normalized !== '' && isset($known[$normalized])) {
                $knownWords++;
            }
        }

        $wordScore = count($words) === 0 ? 0.0 : 0.8 * ($knownWords / count($words));

        return $letterLogLikelihood - 0.65 * $chiSquared + 0.85 * $bigrams + 1.15 * $trigrams + $wordScore;
    }

    public function lettersOnly(string $text): string
    {
        $normalized = class_exists(Normalizer::class)
            ? Normalizer::normalize($text, Normalizer::FORM_D)
            : $text;

        $normalized = $normalized === false ? $text : $normalized;
        $normalized = preg_replace('/\p{Mn}+/u', '', $normalized) ?? $normalized;
        $normalized = strtoupper($normalized);

        return preg_replace('/[^A-Z]/', '', $normalized) ?? '';
    }

    /**
     * @return array<int, float>
     */
    private function frequencies(string $language): array
    {
        return $language === 'Español' ? self::SPANISH_LETTERS : self::ENGLISH_LETTERS;
    }

    /**
     * @return array{frequencies: array<int, float>, bigrams: array<string, float>, trigrams: array<string, float>, words: array<string, bool>}
     */
    private function profile(string $language): array
    {
        if (isset($this->profiles[$language])) {
            return $this->profiles[$language];
        }

        return $this->profiles[$language] = [
            'frequencies' => $this->frequencies($language),
            'bigrams' => $this->compileNgrams($language, 2),
            'trigrams' => $this->compileNgrams($language, 3),
            'words' => array_fill_keys($this->words($language), true),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function ngrams(string $language, int $length): array
    {
        $source = match ([$language, $length]) {
            ['Español', 2] => self::SPANISH_BIGRAMS,
            ['Español', 3] => self::SPANISH_TRIGRAMS,
            ['Inglés', 2] => self::ENGLISH_BIGRAMS,
            default => self::ENGLISH_TRIGRAMS,
        };

        return array_values(array_unique(explode(' ', $source)));
    }

    /**
     * @return array<string, float>
     */
    private function compileNgrams(string $language, int $length): array
    {
        $ngrams = $this->ngrams($language, $length);
        $weights = [];
        $weightTotal = 0.0;

        foreach ($ngrams as $rank => $ngram) {
            $weight = 1 / ($rank + 1);
            $weights[$ngram] = $weight;
            $weightTotal += $weight;
        }

        foreach ($weights as $ngram => $weight) {
            $weights[$ngram] = $weight / $weightTotal;
        }

        return $weights;
    }

    /**
     * @param  array<string, float>  $ngrams
     */
    private function ngramScore(string $letters, int $length, array $ngrams): float
    {
        $total = strlen($letters) - $length + 1;

        if ($total <= 0) {
            return 0.0;
        }

        $score = 0.0;

        for ($index = 0; $index < $total; $index++) {
            $ngram = substr($letters, $index, $length);
            $probability = isset($ngrams[$ngram])
                ? $ngrams[$ngram]
                : 0.00015;
            $score += log($probability);
        }

        return $score / $total;
    }

    /**
     * @return array<int, string>
     */
    private function words(string $language): array
    {
        return explode(' ', $language === 'Español' ? self::SPANISH_WORDS : self::ENGLISH_WORDS);
    }
}
