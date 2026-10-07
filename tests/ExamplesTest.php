<?php

/*
| The examples stay working: each runnable one is started as a separate PHP process.
*/

function uztextExample(string $file, string ...$args): string
{
    $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/examples/'.$file);
    foreach ($args as $arg) {
        $command .= ' '.escapeshellarg($arg);
    }
    exec($command.' 2>&1', $output, $code);
    expect($code)->toBe(0, implode("\n", $output));

    return implode("\n", $output);
}

it('shows each part of the library', function () {
    expect(uztextExample('basics.php'))->toContain('samsung')->toContain('telefon')->toContain("ko'ylak");
});

it('finds products in memory', function () {
    expect(uztextExample('catalog-search.php', 'ntktajy'))->toContain('Чехол для телефона Samsung A54')->toContain('Telefon uchun g‘ilof');
});

it('finds products in a database', function () {
    expect(uztextExample('sqlite-search.php', 'bolalar poyabzal'))->toContain('Болалар пойабзали');
})->skip(! extension_loaded('pdo_sqlite'), 'pdo_sqlite is not installed');
