<p {{ $attributes->class('text-xs text-zinc-500') }}>
    {{ __('Formatting:') }}
    <code>**{{ __('bold') }}**</code>,
    <code>- {{ __('list item') }}</code>,
    <code>## {{ __('heading') }}</code>,
    <code>[{{ __('link text') }}](https://…)</code>.
    {{ __('HTML is not allowed.') }}
</p>
