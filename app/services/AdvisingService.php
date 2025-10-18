<?php

namespace App\Services;

use App\Models\Subject;
use Illuminate\Support\Collection;

class AdvisingService
{
    /**
     * Generate advising list based on curriculum and OCR records.
     *
     * @param  \App\Models\Curriculum  $curriculum
     * @param  \Illuminate\Support\Collection  $ocrRecords  // e.g. collection of ['subject_code' => 'MATH1', 'grade' => 1.5]
     * @return array
     */
    public function generateAdvising($curriculum, Collection $ocrRecords): array
    {
        // 🧾 Get all curriculum subjects
        $subjects = Subject::where('curriculum_id', $curriculum->id)
            ->with('prerequisites')
            ->get();

        // 🟢 Determine passed subjects (from OCR)
        $passedCodes = collect($ocrRecords)
            ->filter(fn($r) => isset($r['grade']) && is_numeric($r['grade']) && $r['grade'] <= 3.0)
            ->pluck('subject_code')
            ->map(fn($code) => strtoupper(trim($code)))
            ->toArray();

        $eligible = [
            'first_sem' => [],
            'second_sem' => [],
        ];

        // 🧮 Track unit count
        $unitCount = ['first_sem' => 0, 'second_sem' => 0];
        $maxUnits = 27;

        foreach ($subjects as $subject) {
            $code = strtoupper(trim($subject->code));

            // ✅ Skip if already passed
            if (in_array($code, $passedCodes)) continue;

            // ✅ Check all prerequisites
            $canEnroll = true;
            foreach ($subject->prerequisites as $pre) {
                $preCode = strtoupper(trim($pre->code));
                if (!in_array($preCode, $passedCodes)) {
                    $canEnroll = false;
                    break;
                }
            }

            if (!$canEnroll) continue;

            // ✅ Check unit cap per semester
            $semKey = strtolower($subject->semester) === 'first' ? 'first_sem' : 'second_sem';

            if ($unitCount[$semKey] + $subject->units <= $maxUnits) {
                $eligible[$semKey][] = [
                    'code' => $subject->code,
                    'title' => $subject->title,
                    'units' => $subject->units,
                    'year_level' => $subject->year_level,
                    'prerequisites' => $subject->prerequisites->pluck('code')->values(),
                ];

                $unitCount[$semKey] += $subject->units;
            }
        }

        return $eligible;
    }
}
