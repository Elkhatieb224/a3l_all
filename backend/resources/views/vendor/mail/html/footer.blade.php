@php
    $emailFooter = \App\Models\Setting::get('email_footer', '');
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
    $emailFooter = $decodeQuillCodeBlocks($emailFooter);
@endphp
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
@if($emailFooter)
{!! $emailFooter !!}
@else
{{ Illuminate\Mail\Markdown::parse($slot) }}
@endif
</td>
</tr>
</table>
</td>
</tr>
