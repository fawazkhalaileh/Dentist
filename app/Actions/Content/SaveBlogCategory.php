<?php

namespace App\Actions\Content;

use App\Actions\Content\Concerns\SavesTranslatedContent;
use App\Models\BlogCategory;
use Illuminate\Support\Facades\DB;

class SaveBlogCategory
{
    use SavesTranslatedContent;

    /**
     * @param  array{sort_order: int, translations: array<string, array<string, ?string>>}  $data
     */
    public function handle(array $data, ?BlogCategory $category = null): BlogCategory
    {
        return DB::transaction(function () use ($data, $category) {
            $category ??= new BlogCategory;
            $category->fill(['sort_order' => $data['sort_order']])->save();

            $this->syncTranslations($category->translations(), $data['translations'], titleKey: 'name');

            return $category->load('translations');
        });
    }
}
