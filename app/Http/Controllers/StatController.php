<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use Illuminate\Http\Request;

class StatController extends Controller
{
    public function summary(Request $request)
    {
        $user = auth('sanctum')->user();

        // ✅ Count done (credited) subjects
        $doneSubjects = Grade::where('user_id', $user->id)
            ->where('status', 'done')
            ->count();

        // ✅ Count currently enrolled subjects
        $enrolledSubjects = Grade::where('user_id', $user->id)
            ->where('status', 'enrolled')
            ->count();

        // ✅ Total subjects from Grades table
        $totalSubjects = Grade::where('user_id', $user->id)->count();

        // ✅ Compute remaining semesters
        // Example: assume 60 total subjects = 8 semesters
        // (you can customize this formula)
        $subjectsPerSem = 7; // adjust depending on your curriculum
        $remainingSubjects = max(0, $totalSubjects - $doneSubjects);
        $remainingSemesters = ceil($remainingSubjects / $subjectsPerSem);

        return response()->json([
            'done_subjects' => $doneSubjects,
            'enrolled_subjects' => $enrolledSubjects,
            'remaining_semesters' => $remainingSemesters,
        ]);
    }
}
