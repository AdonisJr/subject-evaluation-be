<?php

namespace App\Http\Controllers;

use App\Models\UploadedTor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
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
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($tors, 200);
        } catch (Throwable $e) {
            Log::error('Error fetching uploaded TORs: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Upload a TOR to Cloudinary.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            Log::info("🟢 Starting Cloudinary upload for user ID: {$user->id}");

            // ✅ Use direct Cloudinary SDK for Laravel 12
            $cloudinary = new \Cloudinary\Cloudinary(config('cloudinary.cloud_url'));

            $uploadedFile = $cloudinary->uploadApi()->upload(
                $request->file('file')->getRealPath(),
                [
                    'folder' => 'tors',
                    'upload_preset' => config('cloudinary.upload_preset'),
                    'resource_type' => 'auto',
                    'timeout' => 120,
                ]
            );

            $secureUrl = $uploadedFile['secure_url'] ?? null;
            $publicId = $uploadedFile['public_id'] ?? null;
            $resourceType = $uploadedFile['resource_type'] ?? 'auto';

            Log::info("✅ Uploaded to Cloudinary successfully", [
                'secure_url' => $secureUrl,
                'public_id' => $publicId,
                'resource_type' => $resourceType,
            ]);

            // ✅ Save to DB
            $uploadedTor = \App\Models\UploadedTor::create([
                'user_id'   => $user->id,
                'file_path' => $secureUrl,
                'public_id' => $publicId,
                'file_type' => $resourceType,
            ]);

            return response()->json([
                'message' => 'TOR uploaded successfully to Cloudinary',
                'data' => $uploadedTor,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('❌ Cloudinary Upload Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
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
            Log::error('Error showing TOR: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Update TOR status or remarks.
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
        } catch (Throwable $e) {
            Log::error('Error updating TOR: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Delete TOR from Cloudinary + DB.
     */
    public function destroy(UploadedTor $uploadedTor)
    {
        try {
            Log::info("🗑️ Deleting TOR ID: {$uploadedTor->id}");

            if (!empty($uploadedTor->public_id)) {
                Cloudinary::destroy($uploadedTor->public_id);
                Log::info("✅ Deleted from Cloudinary: {$uploadedTor->public_id}");
            }

            $uploadedTor->delete();

            return response()->json(['message' => 'TOR deleted successfully'], 200);
        } catch (Throwable $e) {
            Log::error('Error deleting TOR: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Fetch all uploaded TORs for the authenticated user.
     */
    public function fetchMyTors()
    {
        $user = auth('sanctum')->user();
        // if (!$user) {
        //     return response()->json(['message' => 'Unauthenticated.'], 401);
        // }

        try {
            $tors = UploadedTor::with('user')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($tors, 200);
        } catch (\Throwable $e) {
            Log::error("Error fetching TORs for user {$user->id}: " . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
