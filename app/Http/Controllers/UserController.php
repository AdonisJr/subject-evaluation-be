<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        try {
            $users = User::with('otherInfo.course')->get();
            return response()->json($users, 200);
        } catch (Throwable $e) {
            Log::error('Error fetching users: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email'      => 'required|string|email|unique:users',
                'password'   => 'required|string|min:3',
            ]);

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'email'      => $validated['email'],
                'password'   => Hash::make($validated['password']),
            ]);

            return response()->json([
                'message' => 'User created successfully',
                'user'    => $user,
            ], 201);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Error creating user: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        try {
            return response()->json($user, 200);
        } catch (Throwable $e) {
            Log::error('Error showing user: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'role'       => 'required|in:admin,user',
                'address'    => 'nullable|string|max:255',
                'email'      => 'sometimes|string|email|unique:users,email,' . $user->id,
                'password'   => 'sometimes|string|min:6',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'message' => 'User updated successfully',
                'user'    => $user,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Error updating user: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'request' => $request->all(),
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (Throwable $e) {
            Log::error('Error deleting user: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function getMyInfo(Request $request)
    {
        try {
            // ✅ Check if user is authenticated via Sanctum
            if (!auth('sanctum')->check()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $user = auth('sanctum')->user();

            // ✅ Properly load "otherInfo" and its related "course"
            $user->load('otherInfo.course');

            return response()->json([
                'message' => 'Authenticated user retrieved successfully',
                'user' => $user
            ], 200);
        } catch (Throwable $e) {
            Log::error('Error fetching authenticated user info: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Toggle user active/inactive status.
     */
    public function toggleActive(User $user)
    {
        try {
            // Flip the is_deleted flag
            $user->is_deleted = $user->is_deleted ? 0 : 1;
            $user->save();

            $status = $user->is_deleted ? 'deactivated' : 'activated';

            return response()->json([
                'message' => "User successfully {$status}.",
                'user' => $user
            ], 200);
        } catch (Throwable $e) {
            Log::error('Error toggling user active status: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
            ]);

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
