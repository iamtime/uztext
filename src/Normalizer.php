<?php

namespace Uztext;

/**
 * One spelling for the same Uzbek word: lower case, one apostrophe for oʻ / gʻ / the tutuq belgisi
 * (people type ʻ ʼ ‘ ’ ` ´ or a plain ' interchangeably), no punctuation, single spaces.
 */
final class Normalizer
{
    /** Every character used for the Uzbek apostrophe, in the wild. */
    public const APOSTROPHES = ["\u{02BB}", "\u{02BC}", "\u{2018}", "\u{2019}", '`', "\u{00B4}", "\u{02B9}", "\u{2032}"];

    /** $foldYo: «ё» → «е» for Russian; the transliteration keeps it - the Uzbek «ё» is «yo». */
    public static function normalize(string $text, bool $foldYo = true): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = str_replace(self::APOSTROPHES, "'", $text);
        if ($foldYo) {
            $text = str_replace('ё', 'е', $text);
        }
        // Keep letters, digits and an apostrophe inside a word (o'zbek, ma'lumot); everything else separates words.
        $text = preg_replace("/[^\p{L}\p{N}']+/u", ' ', $text) ?? $text;
        $text = preg_replace("/(?<![\p{L}])'|'(?![\p{L}])/u", ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /** @return list<string> */
    public static function words(string $text): array
    {
        $text = self::normalize($text);

        return $text === '' ? [] : explode(' ', $text);
    }
}
