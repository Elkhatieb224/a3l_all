
@php
    $locale = app()->getLocale();
    $segments = [];
    if ($ad->category ?? null) {
        $segments[] = [
            'url' => route('categories.show', $ad->category->slug),
            'label' => $ad->category->getName($locale),
        ];
    }
    if (($ad->subcategory ?? null) && ($ad->category ?? null)) {
        $chain = [];
        for ($sub = $ad->subcategory; $sub; $sub = $sub->parent ?? null) {
            array_unshift($chain, $sub);
        }
        foreach ($chain as $node) {
            $segments[] = [
                'url' => route('categories.subcategory', [$ad->category->slug, $node->slug]),
                'label' => $node->getName($locale),
            ];
        }
    }
@endphp
@if(count($segments))
    @foreach($segments as $i => $seg)
        @if($i > 0)
            <span class="text-gray-400 select-none mx-0.5" aria-hidden="true">›</span>
        @endif
        <a href="{{ $seg['url'] }}"
           class="{{ $linkClass ?? 'hover:text-primary font-medium break-words' }}">{{ $seg['label'] }}</a>
    @endforeach
@endif
