<?php

namespace App\Actions\Content;

use App\Actions\Content\Concerns\SavesTranslatedContent;
use App\Enums\PublicationStatus;
use App\Models\GalleryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveGalleryItem
{
    use SavesTranslatedContent;

    /**
     * @param  array{
     *     status: string, service_id: ?int, before_media_id: int, after_media_id: int,
     *     consent_confirmed: bool, sort_order: int,
     *     translations: array<string, array<string, ?string>>
     * }  $data
     */
    public function handle(array $data, User $actor, ?GalleryItem $item = null): GalleryItem
    {
        return DB::transaction(function () use ($data, $actor, $item) {
            $item ??= new GalleryItem;
            $status = PublicationStatus::from($data['status']);

            // Keep the original confirmation (who/when) unless consent is withdrawn.
            if ($data['consent_confirmed'] && $item->consent_confirmed_at === null) {
                $item->consent_confirmed_at = now();
                $item->consent_confirmed_by = $actor->id;
            } elseif (! $data['consent_confirmed']) {
                $item->consent_confirmed_at = null;
                $item->consent_confirmed_by = null;
            }

            $item->fill([
                'status' => $status,
                'service_id' => $data['service_id'],
                'before_media_id' => $data['before_media_id'],
                'after_media_id' => $data['after_media_id'],
                'sort_order' => $data['sort_order'],
                'published_at' => $this->publishedAt($status, $item->published_at),
            ])->save();

            $this->syncTranslations($item->translations(), $data['translations'], withSlug: false);

            return $item->load('translations');
        });
    }
}
