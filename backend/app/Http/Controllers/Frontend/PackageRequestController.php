<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PackageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $requests = PackageRequest::with('package')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('frontend.profile.package-requests.index', compact('requests'));
    }

    public function show($id)
    {
        $packageRequest = PackageRequest::with('package')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view('frontend.profile.package-requests.show', compact('packageRequest'));
    }
}
