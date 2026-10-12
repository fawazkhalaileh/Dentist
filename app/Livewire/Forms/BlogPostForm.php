<?php

namespace App\Livewire\Forms;

use App\Enums\PublicationStatus;
use App\Livewire\Forms\Concerns\TranslatedFields;
use App\Models\BlogPost;
use App\Support\Locales;
use Illuminate\Validation\Rule;
use Livewire\Form;

class BlogPostForm extends Form
{
    use TranslatedFields;

    private const FIELDS = ['title', 'slug', 'excerpt', 'body', 'seo_title', 'meta_description'];

    public ?int $postId = null;

    public string $status = 'draft';

    public ?int $blog_category_id = null;

    public ?int $featured_media_id = null;

    /** Optional scheduled publication date (Y-m-d\TH:i). */
    public string $published_at = '';

    /** @var array<string, array<string, ?string>> */
    public array $translations = [];

    public function load(?BlogPost $post): void
    {
        $this->postId = $post?->id;
        $this->translations = $this->blankOrExistingTranslations(self::FIELDS, $post?->translations);

        if ($post) {
            $this->status = $post->status->value;
            $this->blog_category_id = $post->blog_category_id;
            $this->featured_media_id = $post->featured_media_id;
            $this->published_at = $post->published_at?->format('Y-m-d\TH:i') ?? '';
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'published_at' => ['nullable', 'date'],
        ];

        // A post may be written in one language only, but needs at least one.
        $titleKeys = array_map(fn ($l) => "translations.{$l}.title", Locales::supported());

        foreach (Locales::supported() as $l) {
            $others = implode(',', array_diff($titleKeys, ["translations.{$l}.title"]));
            $rules["translations.{$l}.title"] = ["required_without_all:{$others}", 'nullable', 'string', 'max:255'];
            $rules["translations.{$l}.slug"] = $this->slugRules('blog_post_translations', $l, 'blog_post_id', $this->postId);
            $rules["translations.{$l}.excerpt"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$l}.body"] = ["required_with:translations.{$l}.title", 'nullable', 'string', 'max:50000'];
            $rules["translations.{$l}.seo_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$l}.meta_description"] = ['nullable', 'string', 'max:320'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return $this->translatedAttributeNames(self::FIELDS);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'status' => $this->status,
            'blog_category_id' => $this->blog_category_id ?: null,
            'featured_media_id' => $this->featured_media_id,
            'published_at' => $this->published_at ?: null,
            'translations' => $this->translations,
        ];
    }
}
