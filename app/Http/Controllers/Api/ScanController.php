<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Scan;

class ScanController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'data' => 'required|string',
            'type' => 'nullable|string'
        ]);

        $scan = Scan::create([
            'user_id' => $request->user()->id,
            'data' => $request->data,
            'type' => $request->type,
        ]);

        return response()->json($scan, 201);
    }

    public function index(Request $request)
    {
        $scans = $request->user()->scans()->latest()->get();
        return response()->json($scans);
    }
}
