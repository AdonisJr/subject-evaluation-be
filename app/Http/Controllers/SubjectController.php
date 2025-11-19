<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Curriculum;
use Illuminate\Http\Request;
use Throwable;

class SubjectController extends Controller
{
    public function index()
    {
        try {
            $subjects = Subject::with(['curriculum.course', 'prerequisites'])->get();
            return response()->json($subjects);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error fetching subjects', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'curriculum_id' => 'required|exists:curriculums,id',
            'code'          => 'required|unique:subjects,code',
            'name'          => 'required|string',
            'units'         => 'integer|min:1',
            'semester'      => 'nullable|string',
            'year_level'    => 'nullable|integer',
            'prerequisite_ids' => 'array',
        ]);

        $subject = Subject::create($validated);

        if (!empty($validated['prerequisite_ids'])) {
            $subject->prerequisites()->attach($validated['prerequisite_ids']);
        }

        return response()->json($subject->load('prerequisites'), 201);
    }

    public function show($id)
    {
        $subject = Subject::with(['curriculum.course', 'prerequisites'])->findOrFail($id);
        return response()->json($subject);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'curriculum_id' => 'required|exists:curriculums,id',
            'code'          => 'required|unique:subjects,code,' . $id,
            'name'          => 'required|string',
            'units'         => 'integer|min:1',
            'semester'      => 'nullable|string',
            'year_level'    => 'nullable|integer',
            'prerequisite_ids' => 'array',
        ]);

        $subject->update($validated);

        if (isset($validated['prerequisite_ids'])) {
            $subject->prerequisites()->sync($validated['prerequisite_ids']);
        }

        return response()->json($subject->load('prerequisites'));
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return response()->json(['message' => 'Subject deleted successfully']);
    }

    public function getByCurriculum($curriculum_id)
    {
        try {
            $subjects = Subject::where('curriculum_id', $curriculum_id)
                ->with(['curriculum.course', 'prerequisites.subject'])
                ->orderBy('year_level')
                ->orderBy('semester')
                ->get();

            return response()->json($subjects);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error fetching subjects for curriculum',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function getByCurriculum($curriculum_id)
    // {
    //     try {
    //         $curriculum = Curriculum::with('course')->findOrFail($curriculum_id);

    //         $subjects = Subject::where('curriculum_id', $curriculum_id)
    //             ->with(['prerequisites'])
    //             ->orderBy('year_level')
    //             ->orderBy('semester')
    //             ->get();

    //         return response()->json([
    //             'curriculum' => $curriculum,
    //             'subjects' => $subjects,
    //         ]);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'message' => 'Error fetching subjects for curriculum',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
}
