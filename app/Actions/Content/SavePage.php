<?php

namespace App\Actions\Content;

use App\Actions\Content\Concerns\SavesTranslatedContent;
use App\Enums\PublicationStatus;
use App\Models\Page;
use Illuminate\Support\Facades\DB;

class SavePage
{
    use SavesTranslatedContent;

    /**
     * @param  array{status: string, translations: array<string, array<string, ?string>>}  $data
     */
    public function handle(Page $page, array $data): Page
    {
        return DB::transaction(function () use ($page, $data) {
            $status = PublicationStatus::from($data['status']);

            $page->fill([
                'status' => $status,
                'published_at' => $this->publishedAt($status, $page->published_at),
            ])->save();

            $this->syncTranslations($page->translations(), $data['translations'], withSlug: false);

            return $page->load('translations');
        });
    }
}
