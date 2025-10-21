<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CurriculumController extends Controller
{
    /**
     * Display all curriculums with their course info.
     */
    public function index()
    {
        $curriculums = Curriculum::with('course')->orderBy('year_start', 'desc')->get();

        return response()->json($curriculums);
    }

    /**
     * Store a new curriculum (and optionally a new course).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Either provide an existing course_id or create a new one
            'course_id' => 'nullable|exists:courses,id',
            'course_name' => 'nullable|string|max:255',
            'course_code' => 'nullable|string|max:20',
            'name' => 'nullable', // curriculum year like 2022
            'year_start' => 'required|digits:4',
            'year_end' => 'required|digits:4',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $courseId = $validated['course_id'] ?? null;

            // ✅ If no course_id provided, create a new Course
            if (!$courseId && !empty($validated['course_name'])) {
                $course = Course::create([
                    'name' => $validated['course_name'],
                    'code' => $validated['course_code'] ?? strtoupper(substr($validated['course_name'], 0, 5)),
                ]);
                $courseId = $course->id;
            }

            if (!$courseId) {
                return response()->json(['message' => 'Either course_id or course_name is required.'], 422);
            }

            // ✅ Create the curriculum
            $curriculum = Curriculum::create([
                'course_id' => $courseId,
                'name' => $validated['name'],
                'year_start' => $validated['year_start'],
                'year_end' => $validated['year_end'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Curriculum and course saved successfully.',
                'data' => $curriculum->load('course'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save curriculum', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to save curriculum.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing curriculum and optionally its course.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'course_name' => 'nullable|string|max:255',
            'course_code' => 'nullable|string|max:20',
            'name' => 'required|digits:4',
            'year_start' => 'required|digits:4',
            'year_end' => 'required|digits:4',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $curriculum = Curriculum::findOrFail($id);

            // ✅ Update or create course if necessary
            $courseId = $validated['course_id'] ?? $curriculum->course_id;

            if (!empty($validated['course_name'])) {
                $course = Course::updateOrCreate(
                    ['id' => $courseId],
                    [
                        'name' => $validated['course_name'],
                        'code' => $validated['course_code'] ?? strtoupper(substr($validated['course_name'], 0, 5)),
                    ]
                );
                $courseId = $course->id;
            }

            // ✅ Update curriculum
            $curriculum->update([
                'course_id' => $courseId,
                'name' => $validated['name'],
                'year_start' => $validated['year_start'],
                'year_end' => $validated['year_end'],
                'is_active' => $validated['is_active'] ?? $curriculum->is_active,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Curriculum updated successfully.',
                'data' => $curriculum->load('course'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update curriculum', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to update curriculum.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a curriculum.
     */
    public function destroy($id)
    {
        try {
            $curriculum = Curriculum::findOrFail($id);
            $curriculum->delete();

            return response()->json(['message' => 'Curriculum deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete curriculum.', 'error' => $e->getMessage()], 500);
        }
    }
}
