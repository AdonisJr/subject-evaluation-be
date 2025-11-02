<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserOtherInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class UserOtherInfoController extends Controller
{
    public function show()
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $info = UserOtherInfo::where('user_id', $user->id)->first();

            return response()->json([
                'user' => $user,
                'other_info' => $info,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Error fetching user info: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function storeOrUpdate(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            // User table
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',

            // Other info table
            'gender'          => 'nullable|string|max:10',
            'category'        => 'required|string|max:255',
            'dob'             => 'nullable|date',
            'mobile'          => 'nullable|string|max:20',
            'current_address' => 'nullable|string|max:255',
            'permanent_address' => 'nullable|string|max:255',
            'blood_type'      => 'nullable|string|max:10',
            'eye_color'       => 'nullable|string|max:50',
            'height'          => 'nullable|string|max:10',
            'weight'          => 'nullable|string|max:10',
            'religion'        => 'nullable|string|max:50',
            'father'          => 'nullable|string|max:255',
            'mother'          => 'nullable|string|max:255',
        ]);

        try {
            // ✅ Start transaction
            DB::beginTransaction();

            // ✅ Update the User model
            $user->update([
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'email'      => $validated['email'],
            ]);

            // ✅ Update or create UserOtherInfo record
            $otherInfoData = collect($validated)
                ->except(['first_name', 'last_name', 'email'])
                ->toArray();

            $info = UserOtherInfo::updateOrCreate(
                ['user_id' => $user->id],
                $otherInfoData
            );

            // ✅ Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Profile updated successfully.',
                'user' => $user->fresh(),
                'other_info' => $info,
            ], 200);
        } catch (Throwable $e) {
            // ❌ Rollback on error
            DB::rollBack();
            Log::error('Error saving user info: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function adminStoreOrUpdateUser(Request $request, $id = null)
    {
        try {
            // ✅ Ensure only admin can do this
            $admin = auth('sanctum')->user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // ✅ Validate input
            $validated = $request->validate([
                // User table
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email'      => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($id),
                ],

                // Optional password (auto default)
                'password'   => 'nullable|string|min:6',

                // Other info table
                'student_id'            => 'nullable|string|max:10',
                'year_level'            => 'nullable|string|max:10',
                'gender'            => 'nullable|string|max:10',
                'category'          => 'nullable|string|max:255',
                'dob'               => 'nullable|date',
                'mobile'            => 'nullable|string|max:20',
                'current_address'   => 'nullable|string|max:255',
                'permanent_address' => 'nullable|string|max:255',
                'blood_type'        => 'nullable|string|max:10',
                'eye_color'         => 'nullable|string|max:50',
                'height'            => 'nullable|string|max:10',
                'weight'            => 'nullable|string|max:10',
                'religion'          => 'nullable|string|max:50',
                'father'            => 'nullable|string|max:255',
                'mother'            => 'nullable|string|max:255',
            ]);

            DB::beginTransaction();

            // ✅ Find or create user
            $user = \App\Models\User::find($id) ?? new \App\Models\User();

            // ✅ Assign user fields
            $user->first_name = $validated['first_name'];
            $user->last_name  = $validated['last_name'];
            $user->email      = $validated['email'];
            $user->student_id  = $validated['student_id'];
            $user->year_level  = $validated['year_level'];

            // ✅ Password logic
            if (!empty($validated['password'])) {
                $user->password = bcrypt($validated['password']);
            } elseif (!$user->exists) {
                // Default password if new user
                $user->password = bcrypt('password');
            }

            // ✅ Default role for new users
            if (!$user->exists) {
                $user->role = 'user';
            }

            $user->save();

            // ✅ Update or create related user_other_info
            $otherInfoData = collect($validated)
                ->except(['first_name', 'last_name', 'email', 'password'])
                ->toArray();

            $info = \App\Models\UserOtherInfo::updateOrCreate(
                ['user_id' => $user->id],
                $otherInfoData
            );

            DB::commit();

            return response()->json([
                'message' => $id && \App\Models\User::find($id)
                    ? 'User updated successfully.'
                    : 'User created successfully (default password: password).',
                'user' => $user->fresh(),
                'other_info' => $info,
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error saving user (admin): ' . $e->getMessage());

            return response()->json([
                'message' => 'Internal Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
