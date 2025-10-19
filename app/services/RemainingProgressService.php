<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\TorGrade;

class RemainingProgressService
{
    public function compute($tor, $curriculumId)
    {
        // 1️⃣ Total curriculum units
        $totalUnits = Subject::where('curriculum_id', $curriculumId)->sum('units');

        // 2️⃣ Total credited units (is_credited = true)
        $creditedUnits = TorGrade::where('tor_id', $tor->id)
            ->whereNotNull('credited_id')
            ->join('subjects', 'tor_grades.credited_id', '=', 'subjects.id')
            ->sum('subjects.units');

        // 3️⃣ Compute remaining
        $remainingUnits = max(0, $totalUnits - $creditedUnits);

        // 4️⃣ Estimate time remaining (27 units per semester)
        $unitsPerSem = 27;
        $remainingSemesters = ceil($remainingUnits / $unitsPerSem);
        $remainingYears = ceil($remainingSemesters / 2);

        return [
            'course' => $tor->curriculum->course->name ?? 'N/A',
            'total_units' => $totalUnits,
            'credited_units' => $creditedUnits,
            'remaining_units' => $remainingUnits,
            'estimated_semesters_left' => $remainingSemesters,
            'estimated_years_left' => $remainingYears,
        ];
    }
}
