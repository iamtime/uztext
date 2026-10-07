<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Uztext\Uztext;

/**
 * Laravel: an Eloquent model searchable the way people type in Uzbekistan.
 *
 *   // migration
 *   $table->text('search_keys')->nullable();
 *
 *   // model
 *   class Product extends Model
 *   {
 *       use HasUzbekSearch;
 *
 *       protected array $searchable = ['title', 'description'];
 *   }
 *
 *   Product::search('ntktajy')->get();   // finds «Чехол для телефона», «Telefon uchun g‘ilof»
 *
 * search_keys is filled on every save. For rows that existed before, run once:
 *   Product::query()->each(fn (Product $p) => $p->saveQuietly());
 */
trait HasUzbekSearch
{
    public static function bootHasUzbekSearch(): void
    {
        static::saving(function (self $model): void {
            $texts = array_map(fn (string $column) => (string) $model->getAttribute($column), $model->searchable ?? ['title']);
            // Spaces around: " key" then matches the beginning of any word, the first one included.
            $model->setAttribute('search_keys', ' '.Uztext::index(...$texts).' ');
        });
    }

    public function scopeSearch(Builder $query, string $term): void
    {
        $columns = $this->searchable ?? ['title'];
        $like = fn (string $s) => '%'.addcslashes($s, '%_\\').'%';

        $query->where(function (Builder $q) use ($term, $columns, $like): void {
            foreach (Uztext::variants($term) as $variant) {
                // The query as typed (or in the other alphabet / layout) inside a column...
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', $like($variant));
                }
                // ...or every key of it inside search_keys, in any order.
                $keys = array_filter(Uztext::keys($variant), fn (string $k) => mb_strlen($k) >= 2);
                if ($keys) {
                    $q->orWhere(function (Builder $all) use ($keys, $like): void {
                        foreach ($keys as $key) {
                            $all->where('search_keys', 'like', $like(' '.$key));
                        }
                    });
                }
            }
        });
    }
}
