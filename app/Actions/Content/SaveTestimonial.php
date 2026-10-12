<?php

namespace App\Actions\Content;

use App\Actions\Content\Concerns\SavesTranslatedContent;
use App\Enums\PublicationStatus;
use App\Models\Testimonial;
use App\Models\User;

class SaveTestimonial
{
    use SavesTranslatedContent;

    /**
     * @param  array{locale: string, author_name: string, body: string, rating: ?int, service_id: ?int,
     *     consent_confirmed: bool, status: string, sort_order: int}  $data
     */
    public function handle(array $data, User $actor, ?Testimonial $testimonial = null): Testimonial
    {
        $testimonial ??= new Testimonial;
        $status = PublicationStatus::from($data['status']);

        if ($data['consent_confirmed'] && $testimonial->consent_confirmed_at === null) {
            $testimonial->consent_confirmed_at = now();
            $testimonial->consent_confirmed_by = $actor->id;
        } elseif (! $data['consent_confirmed']) {
            $testimonial->consent_confirmed_at = null;
            $testimonial->consent_confirmed_by = null;
        }

        $testimonial->fill([
            'locale' => $data['locale'],
            'author_name' => trim($data['author_name']),
            'body' => trim($data['body']),
            'rating' => $data['rating'],
            'service_id' => $data['service_id'],
            'status' => $status,
            'sort_order' => $data['sort_order'],
            'published_at' => $this->publishedAt($status, $testimonial->published_at),
        ])->save();

        return $testimonial;
    }
}
