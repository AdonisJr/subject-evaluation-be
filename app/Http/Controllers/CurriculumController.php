<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\Course;
use Illuminate\Http\Request;
use Throwable;

class CurriculumController extends Controller
{
    public function index()
    {
        try {
            $curriculums = Curriculum::with('course')->orderBy('created_at', 'desc')->get();
            return response()->json($curriculums);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error fetching curriculums', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id'  => 'required|exists:courses,id',
            'year_start' => 'required|integer',
            'year_end'   => 'required|integer|gte:year_start',
            'is_active'  => 'boolean',
        ]);

        $curriculum = Curriculum::create($validated);
        return response()->json($curriculum, 201);
    }

    public function show($id)
    {
        $curriculum = Curriculum::with(['course', 'subjects'])->findOrFail($id);
        return response()->json($curriculum);
    }

    public function update(Request $request, $id)
    {
        $curriculum = Curriculum::findOrFail($id);

        $validated = $request->validate([
            'course_id'  => 'required|exists:courses,id',
            'year_start' => 'required|integer',
            'year_end'   => 'required|integer|gte:year_start',
            'is_active'  => 'boolean',
        ]);

        $curriculum->update($validated);
        return response()->json($curriculum);
    }

    public function destroy($id)
    {
        $curriculum = Curriculum::findOrFail($id);
        $curriculum->delete();

        return response()->json(['message' => 'Curriculum deleted successfully']);
    }
}
