<x-mail::message>
{{ $messageText }}

@isset($actionUrl)
<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>
@endisset

@php
    $images = $ad->images;
    if (is_string($images)) {
        $decoded = json_decode($images, true);
        $images = is_array($decoded) ? $decoded : [];
    }
    $images = is_array($images) ? $images : [];
    $firstImage = $images[0] ?? null;
    $imageUrl = $firstImage ? asset('storage/' . ltrim((string) $firstImage, '/')) : null;
    $price = is_numeric($ad->price ?? null) ? number_format((float) $ad->price, 0, '.', ',') : null;
    $currency = (string) ($ad->currency ?? '');
@endphp

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:16px; border:1px solid #e5e7eb; border-radius:10px;">
    <tr>
        <td style="padding:14px;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td style="vertical-align:top; width:58%; padding-right:12px;">
                        <div style="font-size:22px; line-height:1.5; font-weight:700; color:#1f2937;">
                            {{ $ad->title }}
                        </div>
                        <div style="margin-top:10px; color:#6b7280; font-size:14px;">
                            #{{ $ad->uid }}
                        </div>
                        @if($price !== null)
                        <div style="margin-top:14px; color:#111827; font-size:30px; font-weight:700;">
                            {{ $price }} {{ $currency }}
                        </div>
                        @endif
                    </td>
                    <td style="vertical-align:top; width:42%; text-align:center;">
                        @if($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $ad->title }}" style="max-width:100%; width:220px; height:auto; border-radius:6px; border:1px solid #e5e7eb;">
                        @else
                        <div style="width:220px; height:160px; margin:0 auto; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:6px; color:#9ca3af; font-size:13px; line-height:160px;">
                            No Image
                        </div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</x-mail::message>
