<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\TorGrade;
use App\Models\Prerequisite;
use Illuminate\Support\Facades\Log;

class AdvisingController extends Controller
{
    public function getAdvisableSubjects($userId, $curriculumId)
    {
        // 1️⃣ Get all subjects for this curriculum
        $subjects = Subject::where('curriculum_id', $curriculumId)
            ->with('prerequisites') // eager load prereqs
            ->get();

        // 2️⃣ Get passed subjects of this student
        $passedSubjects = TorGrade::where('user_id', $userId)
            ->where('grade', '<=', 3) // or whatever your passing grade is
            ->pluck('subject_id')
            ->toArray();

        // 3️⃣ Determine available subjects
        $availableSubjects = [];

        foreach ($subjects as $subject) {
            // Skip if already passed
            if (in_array($subject->id, $passedSubjects)) {
                continue;
            }

            // Get prerequisite subject IDs
            $prereqIds = $subject->prerequisites->pluck('prerequisite_id')->toArray();

            // If no prerequisites, it’s available
            if (empty($prereqIds)) {
                $availableSubjects[] = $subject;
                continue;
            }

            // Check if all prerequisites are passed
            $allPassed = collect($prereqIds)->every(fn($id) => in_array($id, $passedSubjects));

            if ($allPassed) {
                $availableSubjects[] = $subject;
            }
        }

        return response()->json([
            'user_id' => $userId,
            'curriculum_id' => $curriculumId,
            'available_subjects' => $availableSubjects
        ]);
    }
}
