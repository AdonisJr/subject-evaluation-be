<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UploadedTor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; // ✅ Import Auth facade

class UploadedTorController extends Controller
{
    /**
     * List all uploaded TORs (admin use).
     */
    public function index()
    {
        return response()->json(UploadedTor::with('user')->get());
    }

    /**
     * Upload a TOR.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // max 5MB
        ]);

        $user = Auth::user(); // ✅ get current logged-in user

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $path = $request->file('file')->store('tors', 'public');

        $uploadedTor = UploadedTor::create([
            'user_id'  => $user->id, // ✅ use Auth user ID
            'file_path'=> $path
        ]);

        return response()->json([
            'message' => 'TOR uploaded successfully',
            'data'    => $uploadedTor
        ], 201);
    }

    /**
     * Show a specific TOR.
     */
    public function show(UploadedTor $uploadedTor)
    {
        return response()->json($uploadedTor->load('user'));
    }

    /**
     * Update TOR status/remarks (admin only).
     */
    public function update(Request $request, UploadedTor $uploadedTor)
    {
        $validated = $request->validate([
            'status'  => 'sometimes|in:pending,approved,rejected',
            'remarks' => 'nullable|string',
        ]);

        $uploadedTor->update($validated);

        return response()->json([
            'message' => 'TOR updated successfully',
            'data'    => $uploadedTor
        ]);
    }

    /**
     * Delete a TOR (user/admin).
     */
    public function destroy(UploadedTor $uploadedTor)
    {
        Storage::disk('public')->delete($uploadedTor->file_path);
        $uploadedTor->delete();

        return response()->json(['message' => 'TOR deleted successfully']);
    }
}
