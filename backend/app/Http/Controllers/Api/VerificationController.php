<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    private function normalizeOptionalUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^https?:\/\//i', $value)) {
            $value = 'https://' . $value;
        }

        return $value;
    }

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $user = Auth::user();
        $verificationRequirements = Setting::get('verification_requirements_' . app()->getLocale(), '');
        
        $pendingRequest = VerificationRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $lastRequest = VerificationRequest::where('user_id', $user->id)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'is_verified' => $user->is_verified,
                'verification_requirements' => $verificationRequirements,
                'pending_request' => $pendingRequest ? [
                    'id' => $pendingRequest->id,
                    'message' => $pendingRequest->message,
                    'documents' => collect($pendingRequest->documents ?? [])->map(function($doc) {
                        return asset('storage/' . $doc);
                    })->toArray(),
                    'status' => $pendingRequest->status,
                    'created_at' => $pendingRequest->created_at,
                    'business_name' => $pendingRequest->business_name,
                    'business_type' => $pendingRequest->business_type,
                    'responsible_person' => $pendingRequest->responsible_person,
                    'business_address' => $pendingRequest->business_address,
                    'business_phone' => $pendingRequest->business_phone,
                    'instagram_url' => $pendingRequest->instagram_url,
                    'facebook_url' => $pendingRequest->facebook_url,
                    'website_url' => $pendingRequest->website_url,
                    'primary_document_type' => $pendingRequest->primary_document_type,
                    'primary_document' => $pendingRequest->primary_document_path ? asset('storage/' . $pendingRequest->primary_document_path) : null,
                    'storefront_image' => $pendingRequest->storefront_image_path ? asset('storage/' . $pendingRequest->storefront_image_path) : null,
                ] : null,
                'last_request' => $lastRequest ? [
                    'id' => $lastRequest->id,
                    'status' => $lastRequest->status,
                    'admin_notes' => $lastRequest->admin_notes,
                    'reviewed_at' => $lastRequest->reviewed_at,
                ] : null,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if user is already verified
        if ($user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is already verified'
            ], 400);
        }

        // Check if there's a pending request
        $pendingRequest = VerificationRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($pendingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending verification request'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'responsible_person' => 'required|string|max:255',
            'business_address' => 'required|string|max:255',
            'business_phone' => 'required|string|max:50',
            'instagram_url' => 'nullable|string|max:255',
            'facebook_url' => 'nullable|string|max:255',
            'website_url' => 'nullable|string|max:255',
            'primary_document_type' => 'required|in:id,cr',
            'primary_document' => 'required|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
            'storefront_image' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle documents upload
        $documents = [];
        $primaryDocumentPath = $request->file('primary_document')->store('verification/documents', 'public');
        $documents[] = $primaryDocumentPath;

        $storefrontPath = null;
        if ($request->hasFile('storefront_image')) {
            $storefrontPath = store_image_as_webp($request->file('storefront_image'), 'verification/documents');
            $documents[] = $storefrontPath;
        }

        $verificationRequest = VerificationRequest::create([
            'user_id' => $user->id,
            'message' => $request->message,
            'documents' => $documents,
            'status' => 'pending',
            'business_name' => $request->business_name,
            'business_type' => $request->business_type,
            'responsible_person' => $request->responsible_person,
            'business_address' => $request->business_address,
            'business_phone' => $request->business_phone,
            'instagram_url' => $this->normalizeOptionalUrl($request->instagram_url),
            'facebook_url' => $this->normalizeOptionalUrl($request->facebook_url),
            'website_url' => $this->normalizeOptionalUrl($request->website_url),
            'primary_document_type' => $request->primary_document_type,
            'primary_document_path' => $primaryDocumentPath,
            'storefront_image_path' => $storefrontPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Verification request submitted successfully',
            'data' => $verificationRequest
        ], 201);
    }

    public function revoke(Request $request)
    {
        $user = Auth::user();

        if (!$user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not verified'
            ], 400);
        }

        $user->update([
            'is_verified' => false,
        ]);

        // Delete any pending verification requests
        VerificationRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Verification revoked successfully'
        ]);
    }
}
