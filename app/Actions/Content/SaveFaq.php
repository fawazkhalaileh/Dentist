<?php

namespace App\Actions\Content;

use App\Actions\Content\Concerns\SavesTranslatedContent;
use App\Enums\PublicationStatus;
use App\Models\Faq;

class SaveFaq
{
    use SavesTranslatedContent;

    /**
     * @param  array{locale: string, question: string, answer: string, status: string, sort_order: int}  $data
     */
    public function handle(array $data, ?Faq $faq = null): Faq
    {
        $faq ??= new Faq;
        $status = PublicationStatus::from($data['status']);

        $faq->fill([
            'locale' => $data['locale'],
            'question' => trim($data['question']),
            'answer' => trim($data['answer']),
            'status' => $status,
            'sort_order' => $data['sort_order'],
            'published_at' => $this->publishedAt($status, $faq->published_at),
        ])->save();

        return $faq;
    }
}
