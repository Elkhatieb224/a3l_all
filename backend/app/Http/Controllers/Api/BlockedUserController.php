<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BlockedUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $user = Auth::user();
        $blockedUsers = $user->blockedUsers()->with('blockedUser')->get();

        return response()->json([
            'success' => true,
            'data' => $blockedUsers->map(function($blocked) {
                return new UserResource($blocked->blockedUser);
            })
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if trying to block yourself
        if ($request->user_id == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot block yourself'
            ], 400);
        }

        // Check if already blocked
        if ($user->hasBlocked($request->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'User is already blocked'
            ], 400);
        }

        BlockedUser::create([
            'user_id' => $user->id,
            'blocked_user_id' => $request->user_id,
        ]);
        $user->forgetBlockedUserIdsCache();

        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully'
        ], 201);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        
        $blockedUser = BlockedUser::where('user_id', $user->id)
            ->where('blocked_user_id', $id)
            ->firstOrFail();

        $blockedUser->delete();
        $user->forgetBlockedUserIdsCache();

        return response()->json([
            'success' => true,
            'message' => 'User unblocked successfully'
        ]);
    }
}
