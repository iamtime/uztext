<?php

/*
| php examples/sqlite-search.php [query]
|
| uztext with a database (PDO, SQLite in memory; MySQL and PostgreSQL work the same).
| The table keeps a search_keys column = Uztext::index(title). The query is turned into LIKE conditions:
|   title LIKE %variant%   for every spelling of the query, or
|   search_keys LIKE % key% for every key of one spelling.
| On a big catalog put the same strings into a full-text engine (Meilisearch, Elasticsearch) instead of LIKE.
*/

require __DIR__.'/bootstrap.php';

use Uztext\Uztext;

$db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE products (id INTEGER PRIMARY KEY, title TEXT NOT NULL, search_keys TEXT NOT NULL)');

$insert = $db->prepare('INSERT INTO products (title, search_keys) VALUES (?, ?)');
foreach ([
    'Чехол для телефона Samsung A54',
    'Telefon uchun g‘ilof, silikon',
    'Erkaklar ko‘ylagi, paxtali',
    'Болалар пойабзали',
    'Qo‘l soati Casio',
] as $title) {
    // Store the index next to the title (and update it whenever the title changes).
    $insert->execute([$title, ' '.Uztext::index($title).' ']);
}

/** @return list<string> */
function search(PDO $db, string $query): array
{
    $like = fn (string $s) => '%'.addcslashes($s, '%_\\').'%';
    $where = [];
    $bindings = [];
    foreach (Uztext::variants($query) as $variant) {
        // SQLite lowercases only ASCII: compare with a lowercased copy kept in PHP or use a NOCASE collation /
        // MySQL's case-insensitive collation. Here the titles are matched by the keys below anyway.
        $where[] = "LOWER(title) LIKE ? ESCAPE '\'";
        $bindings[] = $like($variant);

        $keys = array_values(array_filter(Uztext::keys($variant), fn (string $k) => mb_strlen($k) >= 2));
        if ($keys) {
            $where[] = '('.implode(' AND ', array_fill(0, count($keys), "search_keys LIKE ? ESCAPE '\\'")).')';
            array_push($bindings, ...array_map(fn (string $k) => $like(' '.$k), $keys));
        }
    }
    if ($where === []) {
        return [];
    }

    $statement = $db->prepare('SELECT title FROM products WHERE '.implode(' OR ', $where).' ORDER BY id');
    $statement->execute($bindings);

    return $statement->fetchAll(PDO::FETCH_COLUMN);
}

$queries = $argc > 1 ? [implode(' ', array_slice($argv, 1))] : ['ntktajy', 'чехлы', 'giloflar', 'кўйлак', 'bolalar poyabzal', 'qol soat'];
foreach ($queries as $query) {
    echo $query, str_repeat(' ', max(1, 18 - mb_strlen($query))), '→ ', implode(' | ', search($db, $query)) ?: 'ничего', "\n";
}
