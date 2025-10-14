<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserOtherInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserOtherInfoController extends Controller
{
    public function show($userId)
    {
        try {
            $info = UserOtherInfo::where('user_id', $userId)->first();
            return response()->json($info, 200);
        } catch (Throwable $e) {
            Log::error('Error fetching user other info: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function storeOrUpdate(Request $request, $userId)
    {
        try {
            $validated = $request->validate([
                'course_id' => 'nullable|exists:courses,id',
                'gender' => 'nullable|string|max:10',
                'category' => 'string|max:255',
                'dob' => 'nullable|date',
                'mobile' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'blood_type' => 'nullable|string|max:10',
                'eye_color' => 'nullable|string|max:50',
                'height' => 'nullable|string|max:10',
                'weight' => 'nullable|string|max:10',
                'religion' => 'nullable|string|max:50',
            ]);

            $info = UserOtherInfo::updateOrCreate(
                ['user_id' => $userId],
                $validated
            );

            return response()->json([
                'message' => 'User other info saved successfully.',
                'data' => $info
            ], 200);
        } catch (Throwable $e) {
            Log::error('Error saving user other info: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
