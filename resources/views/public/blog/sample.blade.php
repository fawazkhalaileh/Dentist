<x-layouts::public :title="$article['title']" :description="$article['excerpt']" type="article" :bleed="true" :noindex="true">
    <x-site.article :title="$article['title']" :excerpt="$article['excerpt']" :category="$article['category']"
        :category-url="route('public.blog.category', ['slug' => $article['category_key']])"
        :date="$article['date']" :author="__('Clinic team (sample)')" :minutes="$article['minutes']" :photo="$article['photo']" :sample="true" :related="$related">
        <x-ui.prose :markdown="$article['body']" />
    </x-site.article>
</x-layouts::public>
