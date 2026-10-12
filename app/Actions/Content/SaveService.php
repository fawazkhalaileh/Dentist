<?php

namespace App\Actions\Content;

use App\Actions\Content\Concerns\SavesTranslatedContent;
use App\Enums\PublicationStatus;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class SaveService
{
    use SavesTranslatedContent;

    /**
     * @param  array{
     *     status: string, is_bookable: bool, duration_minutes: int, sort_order: int,
     *     featured_media_id: ?int, dentist_ids: list<int>,
     *     translations: array<string, array<string, ?string>>,
     *     sections: array<string, list<array{heading: string, body: string}>>,
     *     faqs: array<string, list<array{question: string, answer: string}>>
     * }  $data  Validated by App\Livewire\Forms\ServiceForm
     */
    public function handle(array $data, ?Service $service = null): Service
    {
        return DB::transaction(function () use ($data, $service) {
            $service ??= new Service;
            $status = PublicationStatus::from($data['status']);

            $service->fill([
                'status' => $status,
                'is_bookable' => $data['is_bookable'],
                'duration_minutes' => $data['duration_minutes'],
                'sort_order' => $data['sort_order'],
                'featured_media_id' => $data['featured_media_id'],
                'published_at' => $this->publishedAt($status, $service->published_at),
            ])->save();

            $this->syncTranslations($service->translations(), $data['translations']);
            $this->replaceLocalizedChildren($service->sections(), $data['sections'], 'heading');
            $this->replaceLocalizedChildren($service->faqs(), $data['faqs'], 'question');
            $service->dentists()->sync($data['dentist_ids']);

            return $service->load('translations');
        });
    }
}
