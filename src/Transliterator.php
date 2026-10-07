<?php

namespace Uztext;

/**
 * Uzbek Cyrillic ↔ Latin (the 1995 alphabet): «ўзбекча» ↔ «o'zbekcha», «қовоқ» ↔ «qovoq», «шампунь» ↔ «shampun».
 * Works on normalized text (one apostrophe, lower case). Russian words in Cyrillic come out the way Uzbek sellers spell
 * them in Latin: «телефон» → «telefon», «чехол» → «chexol».
 */
final class Transliterator
{
    private const TO_LATIN = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'y',
        'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'x', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => "'", 'ь' => '', 'ы' => 'i',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'ё' => 'yo', 'ў' => "o'", 'қ' => 'q', 'ғ' => "g'", 'ҳ' => 'h',
    ];

    /** Longest first: «sh» before «s», «o'» before «o». */
    private const TO_CYRILLIC = [
        "o'" => 'ў', "g'" => 'ғ', 'sh' => 'ш', 'ch' => 'ч', 'yo' => 'ё', 'yu' => 'ю', 'ya' => 'я', 'ts' => 'ц',
        'a' => 'а', 'b' => 'б', 'v' => 'в', 'g' => 'г', 'd' => 'д', 'e' => 'е', 'j' => 'ж', 'z' => 'з', 'i' => 'и',
        'y' => 'й', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о', 'p' => 'п', 'r' => 'р', 's' => 'с',
        't' => 'т', 'u' => 'у', 'f' => 'ф', 'x' => 'х', 'q' => 'қ', 'h' => 'ҳ', 'c' => 'с', 'w' => 'в', "'" => 'ъ',
    ];

    private const VOWELS_CYR = 'аеёиоуўэюяъь';

    public static function toLatin(string $text): string
    {
        $text = Normalizer::normalize($text, foldYo: false);
        $out = '';
        $prev = ' ';
        foreach (mb_str_split($text) as $char) {
            $out .= match ($char) {
                // «е» is «ye» at the start of a word and after a vowel: «ер» → «yer», «поезд» → «poyezd».
                'е' => ($prev === ' ' || mb_strpos(self::VOWELS_CYR, $prev) !== false) ? 'ye' : 'e',
                // «ц» is «s» at the start of a word, «ts» inside: «цемент» → «sement», «концерт» → «konsert» in Uzbek
                // spelling, but sellers write «konsert» and «kontsert» - the inside form keeps «ts».
                'ц' => $prev === ' ' ? 's' : 'ts',
                default => self::TO_LATIN[$char] ?? $char,
            };
            $prev = $char;
        }

        return $out;
    }

    public static function toCyrillic(string $text): string
    {
        $chars = mb_str_split(Normalizer::normalize($text));
        $out = '';
        $length = count($chars);
        for ($i = 0; $i < $length;) {
            $two = $chars[$i].($chars[$i + 1] ?? '');
            if (isset($chars[$i + 1], self::TO_CYRILLIC[$two])) {
                $out .= self::TO_CYRILLIC[$two];
                $i += 2;

                continue;
            }
            $char = $chars[$i];
            // «ye» at the start of a word is Cyrillic «е»: «yer» → «ер».
            if ($char === 'y' && ($chars[$i + 1] ?? '') === 'e' && ($i === 0 || $chars[$i - 1] === ' ')) {
                $out .= 'е';
                $i += 2;

                continue;
            }
            $out .= self::TO_CYRILLIC[$char] ?? $char;
            $i++;
        }

        return $out;
    }

    /** Is there any Cyrillic letter in the text. */
    public static function hasCyrillic(string $text): bool
    {
        return (bool) preg_match('/\p{Cyrillic}/u', $text);
    }

    /** Is there any Latin letter in the text. */
    public static function hasLatin(string $text): bool
    {
        return (bool) preg_match('/[a-z]/i', $text);
    }
}
