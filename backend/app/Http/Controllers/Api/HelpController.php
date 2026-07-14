<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class HelpController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();
        $cacheKey = "api:help:v1:{$locale}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($locale) {
            $faqs = Faq::active()
                ->ordered()
                ->select([
                    'id',
                    'question_ar',
                    'question_en',
                    'question_tr',
                    'answer_ar',
                    'answer_en',
                    'answer_tr',
                    'order',
                ])
                ->get();

            return [
                'success' => true,
                'data' => $faqs->map(function ($faq) use ($locale) {
                    return [
                        'id' => $faq->id,
                        'question' => $faq->getQuestion($locale),
                        'answer' => $faq->getAnswer($locale),
                        'order' => $faq->order,
                    ];
                }),
            ];
        });

        $etag = '"' . sha1(json_encode($payload, JSON_UNESCAPED_UNICODE)) . '"';
        if (trim((string) request()->header('If-None-Match')) === $etag) {
            return response()->noContent(304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=3600');
        }

        return response()->json($payload)
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=3600');
    }

    public function sendMessage(Request $request)
    {
        // مصادقة اختيارية: إذا وُجد توكن في الطلب، تحميل المستخدم (لأن المسار عام ولا يمرّ بـ auth:sanctum)
        if ($request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();
            if ($user) {
                Auth::setUser($user);
            }
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.help.message') . ' - Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'pending',
        ];

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
            $data['name'] = Auth::user()->name;
            $data['email'] = Auth::user()->email;
        } else {
            if (!$request->name || !$request->email) {
                return response()->json([
                    'success' => false,
                    'message' => __('frontend.help.guest_name_email_required'),
                    'errors' => [
                        'name' => [__('frontend.help.name')],
                        'email' => [__('frontend.help.email')],
                    ]
                ], 422);
            }
            $data['name'] = $request->name;
            $data['email'] = $request->email;
        }

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = store_image_as_webp($file, 'support');
                if ($path) {
                    $attachmentPaths[] = $path;
                }
            }
        }
        if (!empty($attachmentPaths)) {
            $data['attachments'] = $attachmentPaths;
        }

        $supportMessage = SupportMessage::create($data);

        return response()->json([
            'success' => true,
            'message' => __('frontend.help.message_sent_success'),
            'data' => $supportMessage
        ], 201);
    }
}
