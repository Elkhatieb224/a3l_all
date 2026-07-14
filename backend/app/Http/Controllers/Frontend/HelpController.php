<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\SupportMessage;
use App\Models\SupportMessageReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpController extends Controller
{
    public function index()
    {
        $faqs = Faq::active()->ordered()->get();
        return view('frontend.help.index', compact('faqs'));
    }

    public function contact()
    {
        return view('frontend.help.contact');
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ], [
            'attachments.*.image' => __('frontend.help.attachments_must_be_image'),
            'attachments.*.max' => __('frontend.help.attachments_max_size'),
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = store_image_as_webp($file, 'support');
                if ($path) {
                    $attachmentPaths[] = $path;
                }
            }
        }

        $data = [
            'subject' => $request->subject,
            'message' => $request->message,
            'attachments' => $attachmentPaths,
            'status' => 'pending',
        ];

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
            $data['name'] = Auth::user()->name;
            $data['email'] = Auth::user()->email;
        } else {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
            ]);
            $data['name'] = $request->name;
            $data['email'] = $request->email;
        }

        SupportMessage::create($data);

        return back()->with('success', __('frontend.help.message_sent_success'));
    }

    public function myMessages()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $messages = SupportMessage::where('user_id', Auth::id())
            ->with('admin')
            ->latest()
            ->paginate(15);

        return view('frontend.help.my-messages', compact('messages'));
    }

    public function showMessage($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $message = SupportMessage::where('user_id', Auth::id())
            ->with(['admin', 'replies.user', 'replies.admin'])
            ->findOrFail($id);

        return view('frontend.help.show-message', compact('message'));
    }

    public function reply(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $request->validate([
            'message' => 'required|string|min:10',
        ]);

        $supportMessage = SupportMessage::where('user_id', Auth::id())
            ->findOrFail($id);

        // Create reply
        SupportMessageReply::create([
            'support_message_id' => $supportMessage->id,
            'sender_type' => 'user',
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        // Update status to pending if it was resolved/closed
        if (in_array($supportMessage->status, ['resolved', 'closed'])) {
            $supportMessage->update(['status' => 'pending']);
        }

        return back()->with('success', __('frontend.help.reply_sent_success'));
    }
}
