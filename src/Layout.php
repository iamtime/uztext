<?php

namespace Uztext;

/**
 * A word typed with the wrong keyboard layout: «ыфьыгтп» (Russian layout) is «samsung», «ntktajy» is «телефон».
 * Uzbek sellers and buyers switch between the Russian and the Latin layout all day.
 */
final class Layout
{
    private const EN = "qwertyuiop[]asdfghjkl;'zxcvbnm,.`";

    private const RU = 'йцукенгшщзхъфывапролджэячсмитьбюё';

    /** Latin keys read as the Russian layout: «ntktajy» → «телефон». */
    public static function toRussian(string $text): string
    {
        return self::map(mb_strtolower($text), self::EN, self::RU);
    }

    /** Russian keys read as the Latin layout: «ыфьыгтп» → «samsung». */
    public static function toLatin(string $text): string
    {
        return self::map(mb_strtolower($text), self::RU, self::EN);
    }

    /**
     * The other layout of a query when it is all of one script, else null. Both readings are searched: the wrong layout
     * is only a guess (an English brand typed right also «converts» into nonsense, which simply finds nothing).
     */
    public static function swap(string $text): ?string
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $text) ?? '';
        if ($letters === '') {
            return null;
        }
        if (! preg_match('/[^a-z]/i', $letters)) {
            return self::toRussian($text);
        }
        if (preg_match('/^[\x{0430}-\x{044F}\x{0451}]+$/u', mb_strtolower($letters))) {
            return self::toLatin($text);
        }

        return null;
    }

    private static function map(string $text, string $from, string $to): string
    {
        $from = mb_str_split($from);
        $to = mb_str_split($to);
        $table = array_combine($from, $to);

        return implode('', array_map(fn (string $c) => $table[$c] ?? $c, mb_str_split($text)));
    }
}
