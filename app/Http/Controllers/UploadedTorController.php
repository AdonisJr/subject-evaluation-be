<?php

namespace App\Http\Controllers;

use App\Models\UploadedTor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class UploadedTorController extends Controller
{
    /**
     * List all uploaded TORs (admin use).
     */
    public function index()
    {
        try {
            $tors = UploadedTor::with('user')
                ->orderBy('created_at', 'desc') // ⬅️ newest first
                ->get();

            return response()->json($tors, 200);
        } catch (Throwable $e) {
            Log::error('Error fetching uploaded TORs: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }


    /**
     * Upload a TOR.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            $user = auth('sanctum')->user(); // ✅ Correct way to get authenticated user

            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $path = $request->file('file')->store('tors', 'public');

            $uploadedTor = UploadedTor::create([
                'user_id'   => $user->id,  // ✅ user_id now valid
                'file_path' => $path
            ]);

            return response()->json([
                'message' => 'TOR uploaded successfully',
                'data'    => $uploadedTor,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Error uploading TOR: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }


    /**
     * Show a specific TOR.
     */
    public function show(UploadedTor $uploadedTor)
    {
        try {
            return response()->json($uploadedTor->load('user'), 200);
        } catch (Throwable $e) {
            Log::error('Error showing TOR: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'tor_id' => $uploadedTor->id ?? null,
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Update TOR status/remarks (admin only).
     */
    public function update(Request $request, UploadedTor $uploadedTor)
    {
        try {
            $validated = $request->validate([
                'status'  => 'sometimes|in:pending,approved,rejected',
                'remarks' => 'nullable|string',
            ]);

            $uploadedTor->update($validated);

            return response()->json([
                'message' => 'TOR updated successfully',
                'data'    => $uploadedTor,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Error updating TOR: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'tor_id' => $uploadedTor->id ?? null,
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Delete a TOR (user/admin).
     */
    public function destroy(UploadedTor $uploadedTor)
    {
        try {
            Storage::disk('public')->delete($uploadedTor->file_path);
            $uploadedTor->delete();

            return response()->json(['message' => 'TOR deleted successfully'], 200);
        } catch (Throwable $e) {
            Log::error('Error deleting TOR: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'tor_id' => $uploadedTor->id ?? null,
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
