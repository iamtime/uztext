<?php

namespace Uztext;

/**
 * Search in Uzbek the way people actually type it: in Latin or Cyrillic, with any apostrophe, in the wrong keyboard
 * layout, with endings. A query becomes a few spellings to look for; a text becomes the keys it can be found by.
 */
final class Uztext
{
    /**
     * Spellings of a query, the typed one first: normalized, the other script, the other keyboard layout.
     *
     * @return list<string>
     */
    public static function variants(string $query): array
    {
        $text = Normalizer::normalize($query);
        if ($text === '') {
            return [];
        }
        // The other script and the other layout are read from the query as typed: normalizing first would turn
        // the Uzbek «ё» into «е» («аёллар» → «aellar», not «ayollar») and drop the keys «;» «'» of «ж» «э».
        $typed = mb_strtolower(trim($query), 'UTF-8');
        $variants = [$text];
        $variants[] = Transliterator::hasCyrillic($text) ? Normalizer::normalize(Transliterator::toLatin($typed)) : Transliterator::toCyrillic($text);
        if (($raw = Layout::swap($typed)) !== null && ($swapped = Normalizer::normalize($raw)) !== '') {
            $variants[] = $swapped;
            // A Russian word typed on the Latin layout is also looked for in Latin («ntktajy» → «телефон» → «telefon»);
            // a Latin brand typed on the Russian layout is just itself («ыфьыгтп» → «samsung»).
            if (Transliterator::hasCyrillic($swapped)) {
                $variants[] = Normalizer::normalize(Transliterator::toLatin($raw));
            }
        }

        return array_values(array_unique(array_filter($variants, fn (string $v) => $v !== '')));
    }

    /**
     * Search keys of a text: every word in Latin, stemmed, without apostrophes («Telefonlar uchun g‘iloflar» → telefon, uchun, gilof).
     * Keys of a product title and of a query meet when they mean the same words.
     *
     * @return list<string>
     */
    public static function keys(string $text): array
    {
        $words = Normalizer::words(Transliterator::hasCyrillic($text) ? Transliterator::toLatin($text) : $text);

        // Without the apostrophe: many type «qol», «korpa» for «qo‘l», «ko‘rpa».
        return array_values(array_unique(array_map(fn (string $w) => str_replace("'", '', Stemmer::stem($w)), $words)));
    }

    /**
     * The keys as one string for a search column: «telefon uchun gilof». A word whose ending can be read two ways
     * («poyabzali» - poyabzal + i, «paxtali» - paxta + li) gives both stems: the query's (shortest) key finds either.
     */
    public static function index(string ...$texts): string
    {
        $keys = [];
        foreach ($texts ?: [''] as $text) {
            foreach (Normalizer::words(Transliterator::hasCyrillic($text) ? Transliterator::toLatin($text) : $text) as $word) {
                foreach (Stemmer::stems($word) as $stem) {
                    $keys[] = str_replace("'", '', $stem);
                }
            }
        }

        return implode(' ', array_values(array_unique($keys)));
    }
}
