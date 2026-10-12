<?php

namespace App\Livewire\Forms;

use App\Enums\PublicationStatus;
use App\Models\Faq;
use App\Support\Locales;
use Illuminate\Validation\Rule;
use Livewire\Form;

class FaqForm extends Form
{
    public ?int $faqId = null;

    public string $locale = 'en';

    public string $question = '';

    public string $answer = '';

    public string $status = 'draft';

    public int $sort_order = 0;

    public function load(?Faq $faq): void
    {
        $this->reset();
        $this->locale = Locales::default();

        if ($faq) {
            $this->faqId = $faq->id;
            $this->locale = $faq->locale;
            $this->question = $faq->question;
            $this->answer = $faq->answer;
            $this->status = $faq->status->value;
            $this->sort_order = $faq->sort_order;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'locale' => ['required', Rule::in(Locales::supported())],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'locale' => $this->locale,
            'question' => $this->question,
            'answer' => $this->answer,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
        ];
    }
}
