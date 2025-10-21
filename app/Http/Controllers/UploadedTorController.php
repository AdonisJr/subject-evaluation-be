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
            $tors = UploadedTor::with('user', 'user.otherInfo', 'curriculum.course', 'torGrades', 'advising.subject')
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
            // ✅ Step 1. Validate
            $validated = $request->validate([
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'curriculum_id' => 'required|integer|exists:curricula,id', // add if needed
            ]);

            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            Log::info("🟢 Starting Cloudinary upload for user ID: {$user->id}");

            // ✅ Step 2. Upload to Cloudinary
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

            // ✅ Step 3. Save to DB
            $uploadedTor = \App\Models\UploadedTor::create([
                'user_id' => $user->id,
                'file_path' => $secureUrl,
                'public_id' => $publicId,
                'file_type' => $resourceType,
            ]);

            // ✅ Step 4. Analyze the TOR immediately (sync)
            Log::info("🧩 Starting TOR analysis immediately after upload...");

            $ocrController = new \App\Http\Controllers\TesseractOcrController();
            $analysisResponse = $ocrController->analyzeTor($uploadedTor->id, $request->curriculum_id);

            // If analyzeTor() returns a Response object, extract the data
            if ($analysisResponse instanceof \Illuminate\Http\JsonResponse) {
                $analysisData = $analysisResponse->getData(true);
            } else {
                $analysisData = $analysisResponse;
            }

            Log::info("✅ TOR analysis completed for ID {$uploadedTor->id}");

            // ✅ Step 5. Return everything together
            return response()->json([
                'message' => 'TOR uploaded and analyzed successfully',
                'upload' => $uploadedTor,
                'analysis' => $analysisData,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('❌ Upload + Analyze Error', [
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
    // public function show(UploadedTor $uploadedTor)
    // {
    //     try {
    //         return response()->json($uploadedTor->load('user'), 200);
    //     } catch (Throwable $e) {
    //         Log::error('Error showing TOR: ' . $e->getMessage());
    //         return response()->json(['message' => 'Internal Server Error'], 500);
    //     }
    // }

    /**
     * Show a specific uploaded TOR with relationships.
     */
    public function show($id)
    {
        try {
            $tor = UploadedTor::with([
                'user:id,first_name,last_name,email',
                'user.otherInfo',
                'curriculum:id,name,course_id',
                'curriculum.course:id,code,name',
                'torGrades',
                'advising.subject'
            ])->find($id);

            if (!$tor) {
                return response()->json(['message' => 'TOR not found'], 404);
            }

            return response()->json([
                'message' => 'TOR fetched successfully',
                'data' => $tor
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching TOR details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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

    /**
     * Upload a TOR to Cloudinary.
     */
    public function storeWithCurriculum($curriculum_id, Request $request)
    {
        try {
            Log::info("Incoming TOR upload", [
                'route_curriculum_id' => $curriculum_id,
                'has_file' => $request->hasFile('file'),
                'file_name' => $request->file('file')?->getClientOriginalName(),
            ]);

            // Validate only file (curriculum_id is from route)
            $validated = $request->validate([
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            // Check auth
            $user = auth('sanctum')->user();
            if (!$user) {
                Log::warning("Unauthorized upload attempt");
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            Log::info("Authenticated user ID: {$user->id}");

            // Upload to Cloudinary
            Log::info("☁️ Uploading to Cloudinary...");
            $cloudinary = new \Cloudinary\Cloudinary(config('cloudinary.cloud_url'));

            $upload = $cloudinary->uploadApi()->upload(
                $request->file('file')->getRealPath(),
                [
                    'folder' => 'tors',
                    'upload_preset' => config('cloudinary.upload_preset'),
                    'resource_type' => 'auto',
                    'timeout' => 120,
                ]
            );

            $secureUrl = $upload['secure_url'] ?? null;
            $publicId = $upload['public_id'] ?? null;
            $resourceType = $upload['resource_type'] ?? 'auto';

            Log::info("Cloudinary upload success", [
                'secure_url' => $secureUrl,
                'public_id' => $publicId,
                'resource_type' => $resourceType,
            ]);

            // ✅ Save to DB as 'processing'
            $uploadedTor = \App\Models\UploadedTor::create([
                'user_id' => $user->id,
                'curriculum_id' => $curriculum_id,
                'file_path' => $secureUrl,
                'public_id' => $publicId,
                'file_type' => $resourceType
            ]);

            Log::info("💾 Saved UploadedTor ID: {$uploadedTor->id}");

            // Analyze TOR immediately
            Log::info("Starting OCR analysis...");
            $ocrController = new \App\Http\Controllers\TesseractOcrController();
            $analysisResponse = $ocrController->analyzeTor($uploadedTor->id, $curriculum_id);

            if ($analysisResponse instanceof \Illuminate\Http\JsonResponse) {
                $analysisData = $analysisResponse->getData(true);
            } else {
                $analysisData = $analysisResponse;
            }

            Log::info("OCR analysis complete for TOR ID {$uploadedTor->id}");

            // Mark as success

            return response()->json([
                'message' => 'TOR uploaded and analyzed successfully',
                'upload' => $uploadedTor,
                'analysis' => $analysisData,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning("Validation failed", ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Upload or Analysis Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            //Update TOR status if it was already created
            if (isset($uploadedTor)) {
                $uploadedTor->update(['status' => 'failed']);
            }

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
