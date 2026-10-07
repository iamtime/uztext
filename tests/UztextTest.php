<?php

use Uztext\Layout;
use Uztext\Normalizer;
use Uztext\Stemmer;
use Uztext\Transliterator;
use Uztext\Uztext;

/*
| uztext (packages/uztext): Uzbek the way people type it - any apostrophe, Latin or Cyrillic, the wrong keyboard layout,
| words with endings.
*/

it('brings every apostrophe and punctuation to one spelling', function (string $typed, string $normal) {
    expect(Normalizer::normalize($typed))->toBe($normal);
})->with([
    ['Oʻzbekiston', "o'zbekiston"],
    ['O‘ZBEKISTON', "o'zbekiston"],
    ['o`zbek', "o'zbek"],
    ['G’isht,  qum!', "g'isht qum"],
    ["ma'lumot", "ma'lumot"],
    ["'salom'", 'salom'],
    ['Ёлка - новогодняя', 'елка новогодняя'],
]);

it('writes Uzbek Cyrillic in Latin and back', function (string $cyrillic, string $latin) {
    expect(Transliterator::toLatin($cyrillic))->toBe($latin)
        ->and(Transliterator::toCyrillic($latin))->toBe(Normalizer::normalize($cyrillic));
})->with([
    ['ўзбекча', "o'zbekcha"],
    ['қовоқ', 'qovoq'],
    ['ғишт', "g'isht"],
    ['шахар', 'shaxar'],
    ['чой', 'choy'],
    ['ер', 'yer'],
    ['юлдуз', 'yulduz'],
    ['ҳаво', 'havo'],
]);

it('spells Russian words the way sellers write them in Latin', function () {
    expect(Transliterator::toLatin('Телефон'))->toBe('telefon')
        ->and(Transliterator::toLatin('чехол'))->toBe('chexol')
        ->and(Transliterator::toLatin('шампунь'))->toBe('shampun')
        ->and(Transliterator::toLatin('поезд'))->toBe('poyezd')
        ->and(Transliterator::toLatin('мыло'))->toBe('milo');
});

it('reads a word typed in the wrong keyboard layout', function () {
    expect(Layout::toLatin('ыфьыгтп'))->toBe('samsung')
        ->and(Layout::toRussian('ntktajy'))->toBe('телефон')
        ->and(Layout::swap('ntktajy'))->toBe('телефон')
        ->and(Layout::swap('ыфьыгтп'))->toBe('samsung')
        ->and(Layout::swap('123'))->toBeNull()
        ->and(Layout::swap('мыло soap'))->toBeNull();
});

it('takes the plural, possessive and case endings off, never too much', function (string $word, string $stem) {
    expect(Stemmer::stem($word))->toBe($stem);
})->with([
    ['telefonlar', 'telefon'],
    ['telefonlarning', 'telefon'],
    ['telefonga', 'telefon'],
    ['kitoblarimiz', 'kitob'],
    ["ko'ylaklar", "ko'ylak"],
    ['kiyimlari', 'kiy'],  // the same as «kiyim»: a word and its long forms meet
    ['sumkalar', 'sum'],   // the same as «sumka»
    ['uyda', 'uyda'],      // too short to touch
    ['non', 'non'],
    ['iphone15', 'iphone15'],
]);

it('gives a query its spellings and a text its keys', function () {
    expect(Uztext::variants('ntktajy'))->toBe(['ntktajy', 'нтктажй', 'телефон', 'telefon'])
        ->and(Uztext::variants('Шампунь'))->toBe(['шампунь', 'shampun', 'ifvgeym'])
        ->and(Uztext::keys('Телефонлар учун ғилофлар'))->toBe(['telefon', 'uchun', 'gilof'])
        ->and(Uztext::keys('Telefonlar uchun g‘iloflar'))->toBe(['telefon', 'uchun', 'gilof'])
        ->and(Uztext::index('Чехол для телефона', 'Telefon uchun g‘ilof'))->toBe('chexol dlya telefo telefon uchun gilof');
});

it('meets the forms people type: softened k/q, short and long forms, -li, -cha, no apostrophe, ё', function () {
    $same = fn (string $a, string $b) => expect(Uztext::keys($a))->toBe(Uztext::keys($b));

    $same('ko‘ylagi', 'ko‘ylak');       // k → g before the possessive
    $same('pichog‘i', 'pichoq');        // q → g‘
    $same('kurtkalar', 'kurtka');       // a word and its longer form always meet
    $same('mashinalar', 'mashina');
    $same('qol', 'qo‘l');                // the apostrophe left out
    $same('ayiqcha', 'ayiq');
    $same('аёллар', 'ayollar');          // the Uzbek ё is yo
    expect(Stemmer::stems('poyabzali'))->toContain('poyabzal')       // poyabzal + i
        ->and(Uztext::index('Paxta yostiq'))->toContain(Uztext::keys('paxtali')[0])   // paxta + li
        ->and(Uztext::variants('f`kkfh'))->toContain('ayollar');      // «аёллар» on the Latin layout
});

it('finds nearly every query of the evaluation set, far more than a plain substring search', function () {
    require_once dirname(__DIR__).'/eval/evaluate.php';
    $r = uztext_evaluate();

    expect($r['total'])->toBeGreaterThanOrEqual(300)
        ->and($r['uztext'] / $r['total'])->toBeGreaterThanOrEqual(0.98)
        ->and($r['plain'] / $r['total'])->toBeLessThan(0.3)
        ->and(max(array_column($r['kinds'], 'results')))->toBeLessThan(2.0); // and not by finding everything
});
