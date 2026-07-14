<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginIpBlock;
use Illuminate\Http\Request;

class LoginIpBlockController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'ip_address' => 'nullable|string|max:45',
            'channel' => 'nullable|in:api,web,admin',
            'permanent_only' => 'nullable|boolean',
            'active_only' => 'nullable|boolean',
        ]);

        $query = LoginIpBlock::query()->orderByDesc('updated_at');

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('ip_address')) {
            $term = addcslashes($request->ip_address, '%_\\');
            $query->where('ip_address', 'like', '%'.$term.'%');
        }

        if ($request->boolean('permanent_only')) {
            $query->where('is_permanent', true);
        }

        if ($request->boolean('active_only')) {
            $query->where(function ($q) {
                $q->where('is_permanent', true)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('blocked_until')->where('blocked_until', '>', now());
                    });
            });
        }

        $blocks = $query->paginate(30)->withQueryString();

        return view('admin.login-ip-blocks.index', compact('blocks'));
    }

    public function show(LoginIpBlock $login_ip_block)
    {
        return view('admin.login-ip-blocks.show', ['block' => $login_ip_block]);
    }

    public function unblock(Request $request, LoginIpBlock $login_ip_block)
    {
        LoginIpBlock::releaseFromAdmin($login_ip_block->ip_address, $login_ip_block->channel);

        return redirect()
            ->route('admin.login-ip-blocks.show', $login_ip_block)
            ->with('success', __('admin.login_ip_blocks.unblocked'));
    }

    public function makePermanent(Request $request, LoginIpBlock $login_ip_block)
    {
        LoginIpBlock::markPermanentByAdmin($login_ip_block->ip_address, $login_ip_block->channel);

        return redirect()
            ->route('admin.login-ip-blocks.show', $login_ip_block)
            ->with('success', __('admin.login_ip_blocks.marked_permanent'));
    }

    public function updateNotes(Request $request, LoginIpBlock $login_ip_block)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $login_ip_block->update([
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', __('admin.login_ip_blocks.notes_saved'));
    }
}
