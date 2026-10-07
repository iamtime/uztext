<?php

/*
| php eval/evaluate.php
|
| Runs the evaluation set (dataset.php) through the search from examples/catalog-search.php (the query's spellings
| as a substring of the title, or all its keys in the title's keys) and through a plain substring search,
| and prints how many queries find their product, by kind of difficulty, and how many products a query brings.
*/

foreach (['Normalizer', 'Transliterator', 'Layout', 'Stemmer', 'Uztext'] as $class) {
    require_once __DIR__.'/../src/'.$class.'.php';
}

use Uztext\Uztext;

/**
 * @return array{kinds: array<string, array{total: int, uztext: int, plain: int, results: float}>, total: int, uztext: int, plain: int, misses: list<string>}
 */
function uztext_evaluate(): array
{
    $data = require __DIR__.'/dataset.php';
    $products = $data['products'];
    $index = array_map(fn (string $title) => ' '.Uztext::index($title).' ', $products);
    $lower = array_map(fn (string $title) => mb_strtolower($title), $products);

    $search = function (string $q) use ($products, $index, $lower): array {
        $found = [];
        $variants = Uztext::variants($q);
        $groups = [];
        foreach ($variants as $variant) {
            $keys = array_values(array_filter(Uztext::keys($variant), fn (string $k) => mb_strlen($k) >= 2));
            if ($keys) {
                $groups[implode(' ', $keys)] = $keys;
            }
        }
        foreach ($products as $id => $title) {
            foreach ($variants as $variant) {
                if (str_contains($lower[$id], $variant)) {
                    $found[] = $id;

                    continue 2;
                }
            }
            foreach ($groups as $keys) {
                if (array_filter($keys, fn (string $k) => ! str_contains($index[$id], $k)) === []) {
                    $found[] = $id;

                    continue 2;
                }
            }
        }

        return $found;
    };
    $plain = fn (string $q) => array_keys(array_filter($lower, fn (string $t) => str_contains($t, mb_strtolower(trim($q)))));

    // Two kinds made here with our own tables, not the library's: the wrong keyboard layout and other apostrophes.
    $ru = 'йцукенгшщзхъфывапролджэячсмитьбюё';
    $en = 'qwertyuiop[]asdfghjkl;\'zxcvbnm,.`';
    $toEn = array_combine(mb_str_split($ru), str_split($en));
    $toRu = array_flip($toEn);
    $queries = $data['queries'];
    foreach ($data['queries'] as [$q, $id, $kind]) {
        // Only what a Russian / English keyboard can type (no ў қ ғ ҳ, no ‘).
        if ($kind === 'exact' && ! preg_match('/[ўқғҳЎҚҒҲ‘]/u', $q)) {
            $swap = preg_match('/\p{Cyrillic}/u', $q) ? $toEn : $toRu;
            $typed = implode('', array_map(fn (string $c) => $swap[$c] ?? $c, mb_str_split($q)));
            if ($typed !== $q) {
                $queries[] = [$typed, $id, 'layout'];
            }
        }
        if (str_contains($q, '‘')) {
            foreach (["'", '`', '’', 'ʻ'] as $mark) {
                $queries[] = [str_replace('‘', $mark, $q), $id, 'apostrophe'];
            }
        }
    }

    $report = ['kinds' => [], 'total' => 0, 'uztext' => 0, 'plain' => 0, 'misses' => []];
    foreach ($queries as [$q, $id, $kind]) {
        $found = $search($q);
        $hit = in_array($id, $found, true);
        $plainHit = in_array($id, $plain($q), true);
        $row = &$report['kinds'][$kind];
        $row ??= ['total' => 0, 'uztext' => 0, 'plain' => 0, 'results' => 0.0];
        $row['total']++;
        $row['uztext'] += (int) $hit;
        $row['plain'] += (int) $plainHit;
        $row['results'] += count($found);
        unset($row);
        $report['total']++;
        $report['uztext'] += (int) $hit;
        $report['plain'] += (int) $plainHit;
        if (! $hit) {
            $report['misses'][] = "[{$kind}] «{$q}» → {$products[$id]}";
        }
    }
    foreach ($report['kinds'] as &$row) {
        $row['results'] = round($row['results'] / max(1, $row['total']), 1);
    }

    return $report;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $r = uztext_evaluate();
    $pct = fn (int $a, int $b) => str_pad(number_format($b ? $a / $b * 100 : 0, 1).'%', 7, ' ', STR_PAD_LEFT);
    echo str_pad('kind', 12).str_pad('queries', 9).'   uztext   plain   results/query'.PHP_EOL;
    foreach ($r['kinds'] as $kind => $row) {
        echo str_pad($kind, 12).str_pad((string) $row['total'], 9).$pct($row['uztext'], $row['total']).' '.$pct($row['plain'], $row['total']).'   '.$row['results'].PHP_EOL;
    }
    echo str_pad('all', 12).str_pad((string) $r['total'], 9).$pct($r['uztext'], $r['total']).' '.$pct($r['plain'], $r['total']).PHP_EOL;
    if ($r['misses']) {
        echo PHP_EOL.'Not found:'.PHP_EOL.implode(PHP_EOL, $r['misses']).PHP_EOL;
    }
}
