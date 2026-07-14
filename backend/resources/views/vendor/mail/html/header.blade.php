@props(['url'])
@php
    $emailHeader = \App\Models\Setting::get('email_header', '');
    $decodeQuillCodeBlocks = static function (?string $html): string {
        $value = (string) ($html ?? '');
        if ($value === '') {
            return '';
        }

        return preg_replace_callback(
            '/<pre[^>]*ql-syntax[^>]*>(.*?)<\/pre>/is',
            static function (array $matches): string {
                return html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            },
            $value
        ) ?? $value;
    };
    $emailHeader = $decodeQuillCodeBlocks($emailHeader);
    $logoPath = public_path('logo.png');
    $logoExists = file_exists($logoPath);
    $appUrl = rtrim((string) config('app.url', ''), '/');
    $logoSrc = $logoExists
        ? ($appUrl !== '' ? $appUrl . '/logo.png' : asset('logo.png'))
        : '';
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-align: center;">
@if($logoExists)
<img src="{{ $logoSrc }}" class="logo" alt="{{ config('app.name') }}" style="max-height: 60px; height: auto; display: block; border: 0; margin: 0 auto;" width="120">
@endif

@if($emailHeader)
<div style="margin-top: 10px; text-align: center;">
{!! $emailHeader !!}
</div>
@else
    @if(!$logoExists)
    <span style="font-size: 24px; font-weight: bold; color: #002C60;">{{ config('app.name') }}</span>
    @endif
@endif
</a>
</td>
</tr>
