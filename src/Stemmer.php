<?php

namespace Uztext;

/**
 * A rule-based stemmer for Uzbek in Latin: the plural, possessive and case endings come off, so «telefonlar»,
 * «telefonlarning», «telefonga» all give «telefon». Endings are stripped from the end in the order Uzbek glues them
 * (stem + suffix + plural + possessive + case), and only while the stem stays long enough to mean something. The
 * passes repeat until nothing changes, so a word and its longer forms always meet («kurtka» and «kurtkalar»).
 */
final class Stemmer
{
    /** Shorter stems are left alone: «uy», «non», «tuz» must not lose letters. */
    private const MIN_STEM = 3;

    /** Words shorter than this are not stemmed at all. */
    private const MIN_WORD = 5;

    private const CASES = ['gacha', 'dagi', 'ning', 'dan', 'tan', 'ga', 'ka', 'qa', 'da', 'ta', 'ni', 'na'];

    private const POSSESSIVE = ['imiz', 'ingiz', 'lari', 'miz', 'ngiz', 'im', 'ing', 'si', 'ri', 'i'];

    private const PLURAL = ['lar'];

    /** Word-forming suffixes people add in queries: «ayiqcha» (a little bear), «paxtali» (cotton). */
    private const SUFFIXES = ['cha', 'li'];

    /** A possessive after k/q softens it: «ko‘ylak» → «ko‘ylagi», «pichoq» → «pichog‘i». */
    private const SOFTENING = ['i', 'im', 'ing', 'imiz', 'ingiz'];

    /** The stem of a word; of two readings, the shorter - it finds both (keys are matched as substrings). */
    public static function stem(string $word): string
    {
        $stems = self::stems($word);
        usort($stems, fn (string $a, string $b) => mb_strlen($a) <=> mb_strlen($b));

        return $stems[0];
    }

    /**
     * Every stem a word can have: one, or two when «…li» reads both as the suffix «li» and as a possessive «i»
     * after «l» («paxtali» - paxta, «poyabzali» - poyabzal).
     *
     * @return list<string>
     */
    public static function stems(string $word): array
    {
        $word = Normalizer::normalize($word);
        if (mb_strlen($word) < self::MIN_WORD || str_contains($word, ' ') || preg_match('/\d/', $word)) {
            return [$word];
        }

        return array_values(array_unique([self::reduce($word, true), self::reduce($word, false)]));
    }

    private static function reduce(string $word, bool $liIsSuffix): string
    {
        for ($pass = 0; $pass < 3; $pass++) {
            $before = $word;
            $word = self::strip($word, self::CASES);
            $word = self::possessive($word, $liIsSuffix);
            foreach ([self::PLURAL, self::SUFFIXES] as $endings) {
                $word = self::strip($word, $endings);
            }
            if ($word === $before || mb_strlen($word) < self::MIN_WORD) {
                break;
            }
        }

        return $word;
    }

    private static function possessive(string $word, bool $liIsSuffix): string
    {
        foreach (self::POSSESSIVE as $ending) {
            if (! str_ends_with($word, $ending) || mb_strlen($word) - mb_strlen($ending) < self::MIN_STEM) {
                continue;
            }
            // «paxtali», «bug‘li»: in this reading the «i» belongs to the suffix «li», stripped below.
            if ($liIsSuffix && $ending === 'i' && str_ends_with($word, 'li')) {
                return $word;
            }
            $stem = mb_substr($word, 0, mb_strlen($word) - mb_strlen($ending));
            if (in_array($ending, self::SOFTENING, true)) {
                $stem = match (true) {
                    str_ends_with($stem, "g'") => mb_substr($stem, 0, -2).'q',
                    str_ends_with($stem, 'g') && mb_strlen($stem) > self::MIN_STEM => mb_substr($stem, 0, -1).'k',
                    default => $stem,
                };
            }

            return $stem;
        }

        return $word;
    }

    /** @param  list<string>  $endings */
    private static function strip(string $word, array $endings): string
    {
        foreach ($endings as $ending) {
            if (str_ends_with($word, $ending) && mb_strlen($word) - mb_strlen($ending) >= self::MIN_STEM) {
                return mb_substr($word, 0, mb_strlen($word) - mb_strlen($ending));
            }
        }

        return $word;
    }
}
