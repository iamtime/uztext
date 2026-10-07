<?php

/*
| php examples/basics.php
|
| Each part of uztext on its own: apostrophes, alphabets, keyboard layout, word endings.
*/

require __DIR__.'/bootstrap.php';

use Uztext\Layout;
use Uztext\Normalizer;
use Uztext\Stemmer;
use Uztext\Transliterator;
use Uztext\Uztext;

$show = function (string $what, string|array $value): void {
    echo $what, str_repeat(' ', max(1, 36 - mb_strlen($what))), is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value, "\n";
};

// Any apostrophe (ʻ ‘ ’ ` ') becomes one, the case and the punctuation go.
$show("normalize('Oʻzbekiston!')", Normalizer::normalize('Oʻzbekiston!'));

// Latin ↔ Cyrillic, Russian words the way sellers write them in Latin.
$show("toLatin('ўзбекча')", Transliterator::toLatin('ўзбекча'));
$show("toLatin('чехол')", Transliterator::toLatin('чехол'));
$show("toCyrillic('qo‘ylak')", Transliterator::toCyrillic('qo‘ylak'));

// Typed with the wrong keyboard layout.
$show("swap('ыфьыгтп')", Layout::swap('ыфьыгтп'));
$show("swap('ntktajy')", Layout::swap('ntktajy'));

// Endings: plural, possessive, cases; softening before an ending.
$show("stem('telefonlarga')", Stemmer::stem('telefonlarga'));
$show("stem('ko‘ylagi')", Stemmer::stem("ko'ylagi"));
$show("stems('poyabzali')", Stemmer::stems('poyabzali')); // both readings of «…li» go to the index

// What the search uses.
$show("variants('ntktajy')", Uztext::variants('ntktajy'));
$show("keys('Telefonlar uchun g‘iloflar')", Uztext::keys('Telefonlar uchun g‘iloflar'));
$show("index('Чехол для телефона')", Uztext::index('Чехол для телефона'));
