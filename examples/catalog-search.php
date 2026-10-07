<?php

/*
| php examples/catalog-search.php [query]
|
| Product search in memory: the way to use uztext with any storage.
|  1. When a product is saved, keep Uztext::index(title) next to the title.
|  2. A query matches a product when one of Uztext::variants(query) is inside the title,
|     or every key of a variant (Uztext::keys) is inside the product's index.
| eval/evaluate.php measures exactly this search.
*/

require __DIR__.'/bootstrap.php';

use Uztext\Uztext;

final class CatalogSearch
{
    /** @var array<int, array{title: string, lower: string, index: string}> */
    private array $products = [];

    public function add(int $id, string $title): void
    {
        // Spaces around, so " key" also matches the beginning of the first word.
        $this->products[$id] = ['title' => $title, 'lower' => mb_strtolower($title), 'index' => ' '.Uztext::index($title).' '];
    }

    /** @return list<string> titles found */
    public function search(string $query): array
    {
        $variants = Uztext::variants($query);
        $groups = [];
        foreach ($variants as $variant) {
            $keys = array_values(array_filter(Uztext::keys($variant), fn (string $k) => mb_strlen($k) >= 2));
            if ($keys) {
                $groups[implode(' ', $keys)] = $keys;
            }
        }

        $found = [];
        foreach ($this->products as $product) {
            foreach ($variants as $variant) {
                if (str_contains($product['lower'], $variant)) {
                    $found[] = $product['title'];

                    continue 2;
                }
            }
            foreach ($groups as $keys) {
                if (array_filter($keys, fn (string $k) => ! str_contains($product['index'], $k)) === []) {
                    $found[] = $product['title'];

                    continue 2;
                }
            }
        }

        return $found;
    }
}

$catalog = new CatalogSearch;
foreach ([
    'Чехол для телефона Samsung A54',
    'Telefon uchun g‘ilof, silikon',
    'Erkaklar ko‘ylagi, paxtali',
    'Болалар пойабзали',
    'Qo‘l soati Casio',
    'Наушники беспроводные',
] as $id => $title) {
    $catalog->add($id + 1, $title);
}

$queries = $argc > 1 ? [implode(' ', array_slice($argv, 1))] : ['ntktajy', 'чехол', 'g‘iloflar', 'ko‘ylak', 'bolalar poyabzal', 'qol soat', 'ыфьыгтп'];
foreach ($queries as $query) {
    echo $query, str_repeat(' ', max(1, 18 - mb_strlen($query))), '→ ', implode(' | ', $catalog->search($query)) ?: 'ничего', "\n";
}
