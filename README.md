# uztext

Узбекский текст для поиска так, как его на самом деле набирают люди.

- **Апострофы.** `oʻ`, `o‘`, `o’`, `o\``, `o'` приводятся к одному написанию.
- **Латиница и кириллица.** `ўзбекча` ↔ `o'zbekcha`. Русские слова записываются так, как их пишут продавцы латиницей: `телефон` → `telefon`, `чехол` → `chexol`.
- **Неправильная раскладка.** `ыфьыгтп` → `samsung`, `ntktajy` → `телефон`.
- **Окончания.** Отбрасываются окончания множественного числа, принадлежности и падежей: `telefonlarga`, `telefonlarning` → `telefon`. Учитывается смягчение перед окончанием (`ko‘ylagi` → `ko‘ylak`, `pichog‘i` → `pichoq`) и суффиксы `-li`, `-cha` (`paxtali`, `ayiqcha`).
- **Без апострофа.** `qol`, `korpa` находят `qo‘l`, `ko‘rpa`: ключи хранятся без апострофа.
- **Кириллица с «ё».** Узбекское `ё` читается как `yo` (`аёллар` → `ayollar`).

## Установка

```bash
composer require iamtime/uztext
```

PHP 8.2+, расширение `mbstring`. Зависимостей нет.

## Пример

```php
use Uztext\Uztext;

Uztext::variants('ntktajy');                  // ['ntktajy', 'нтктажй', 'телефон', 'telefon'] - что искать
Uztext::keys('Telefonlar uchun g‘iloflar');   // ['telefon', 'uchun', 'gilof'] - ключи текста
Uztext::index('Чехол для телефона');          // "chexol dlya telefo" - строка для поискового поля
```

Сначала сохраните `Uztext::index(...)` названий в отдельное поле. Потом ищите по `Uztext::variants($q)` в названиях и по `Uztext::keys($q)` в этом поле.

Отдельные части:
- `Normalizer::normalize()`;
- `Transliterator::toLatin()` / `toCyrillic()`;
- `Layout::swap()`;
- `Stemmer::stem()` / `stems()` (две основы, когда окончание читается двояко: `poyabzali` - poyabzal + i, `paxtali` - paxta + li).

## Примеры

Папка `examples/` запускается без установки: `php examples/basics.php`.

| Файл | Что показывает |
|---|---|
| `basics.php` | каждую часть отдельно: апострофы, алфавиты, раскладку, окончания |
| `catalog-search.php` | поиск по каталогу в памяти, тот же, что меряет `eval/evaluate.php` |
| `sqlite-search.php` | поиск в базе через PDO: поле `search_keys` и условия `LIKE` |
| `laravel/HasUzbekSearch.php` | трейт для модели Eloquent: `search_keys` заполняется при сохранении, `Product::search('ntktajy')` |

Своё слово можно проверить так: `php examples/catalog-search.php кўйлаклар`.

## Тесты

```bash
composer install
composer test
```

## Качество

Оценочный набор `eval/dataset.php`: 60 товаров магазина (русские, узбекские латиницей и кириллицей названия) и 390 запросов к ним. Запуск: `php eval/evaluate.php`.

| Вид запроса | Запросов | uztext | Поиск подстроки |
|---|---|---|---|
| Как в названии | 74 | 100% | 97% |
| Другой алфавит | 45 | 100% | 0% |
| С окончанием | 47 | 100% | 21% |
| Другой порядок слов | 26 | 100% | 0% |
| Несколько сложностей сразу | 31 | 100% | 0% |
| Другой апостроф | 104 | 100% | 0% |
| Не та раскладка | 63 | 100% | 0% |
| **Всего** | **390** | **100%** | **21%** |

В среднем запрос находит 1–1,4 товара, то есть совпадения не размазываются на весь каталог. Набор составлен вместе с правилами, поэтому это верхняя оценка: живые запросы стоит добавлять в набор по мере появления.

## O'zbekcha

Qidiruv uchun o'zbek matni: istalgan apostrof, lotin va kirill yozuvi, noto'g'ri klaviatura tartibi, qo'shimchalar (`telefonlarga` → `telefon`).

O'rnatish: `composer require iamtime/uztext`. Misollar `examples/` papkasida: `basics.php` (har bir qism alohida), `catalog-search.php` (xotiradagi katalog bo'yicha qidiruv), `sqlite-search.php` (PDO orqali bazada qidiruv), `laravel/HasUzbekSearch.php` (Eloquent modeli uchun trait). Ishga tushirish: `php examples/basics.php`.

## English

Uzbek text for search: any apostrophe, Latin and Cyrillic scripts, a wrong keyboard layout, word endings (`telefonlarga` → `telefon`). MIT.

Install: `composer require iamtime/uztext`. Examples are in `examples/`: `basics.php` shows each part on its own, `catalog-search.php` searches a catalog in memory, `sqlite-search.php` searches a database through PDO, and `laravel/HasUzbekSearch.php` is a trait for an Eloquent model. Run one with `php examples/basics.php`. Tests: `composer install && composer test`.
