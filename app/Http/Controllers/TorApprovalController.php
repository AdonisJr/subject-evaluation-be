<?php

namespace App\Http\Controllers;

use App\Models\TorGrade;
use App\Models\Advising;
use App\Models\Grade;
use App\Models\UserOtherInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TorApprovalController extends Controller
{
    public function approve(Request $request)
    {
        $validated = $request->validate([
            'tor_id' => 'required|exists:uploaded_tors,id',
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'tor_grades' => 'array',
            'advising' => 'array',
        ]);

        DB::beginTransaction();

        try {
            /** ------------------------------------------------
             * ✅ 1. Update student other_info
             * ------------------------------------------------ */
            $otherInfo = UserOtherInfo::where('user_id', $validated['user_id'])->first();

            if ($otherInfo) {
                $otherInfo->update([
                    'course_id' => $validated['course_id'],
                ]);
            }

            /** ------------------------------------------------
             * ✅ 2. Save credited TOR grades only
             * ------------------------------------------------ */
            $creditedGrades = collect($request->tor_grades)
                ->filter(fn($g) => $g['is_credited'] ?? false)
                ->map(fn($g) => [
                    'user_id' => $validated['user_id'],
                    'tor_id' => $validated['tor_id'],
                    'credited_id' => $g['credited_id'] ?? null,
                    'extracted_code' => $g['extracted_code'] ?? null,
                    'credited_code' => $g['credited_code'] ?? null,
                    'title' => $g['title'] ?? null,
                    'grade' => $g['grade'] ?? null,
                    'credits' => $g['credits'] ?? null,
                    'is_credited' => $g['is_credited'] ?? null,
                    'percent_grade' => $g['percent_grade'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->values();

            // Remove old ones first
            TorGrade::where('tor_id', $validated['tor_id'])->delete();
            TorGrade::insert($creditedGrades->toArray());

            /** ------------------------------------------------
             * ✅ 3. Mirror credited TOR grades into GRADES table
             * ------------------------------------------------ */
            $gradesFromTOR = $creditedGrades->map(fn($g) => [
                'user_id'       => $validated['user_id'],
                'subject_id'    => $g['credited_id'] ?? null,   // matched subject
                'credited_id'   => $g['credited_id'] ?? null,
                'tor_grade_id'  => $g['tor_id'] ?? null,
                'advising_id'   => null,
                'type'          => 'credited',
                'status'        => 'done',
                'year_level'    => null,
                'grade'         => $g['grade'] ?? null,
                'grade_percent' => $g['percent_grade'] ?? null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            Grade::insert($gradesFromTOR->toArray());

            /** ------------------------------------------------
             * ✅ 4. Save advising subjects
             * ------------------------------------------------ */
            Advising::where('uploaded_tor_id', $validated['tor_id'])->delete();

            $advising = collect($request->advising)->map(fn($a) => [
                'user_id'        => $validated['user_id'],
                'uploaded_tor_id'=> $validated['tor_id'],
                'subject_id'     => $a['subject_id'],
                'subject_code'   => $a['subject_code'],
                'semester'       => $a['semester'],
                'year_level'     => $a['year_level'],
                'subject_title'  => $a['subject_title'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ])->values();

            Advising::insert($advising->toArray());

            /** ------------------------------------------------
             * ✅ 5. Mirror advising subjects into GRADES table
             * ------------------------------------------------ */
            $gradesFromAdvising = $advising->map(fn($a) => [
                'user_id'       => $validated['user_id'],
                'subject_id'    => $a['subject_id'] ?? null,
                'credited_id'   => null,
                'tor_grade_id'  => $a['uploaded_tor_id'] ?? null,
                'advising_id'   => null,
                'type'          => 'advising',
                'status'        => 'enrolled', // 👈 for advising subjects
                'year_level'    => $a['year_level'] ?? null,
                'grade'         => null,
                'grade_percent' => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            Grade::insert($gradesFromAdvising->toArray());

            /** ------------------------------------------------
             * ✅ 6. Commit all
             * ------------------------------------------------ */
            DB::commit();

            return response()->json([
                'message' => 'TOR approved successfully',
                'credited_grades_count' => $creditedGrades->count(),
                'grades_inserted_count' => $gradesFromTOR->count() + $gradesFromAdvising->count(),
                'advising_count' => $advising->count(),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TOR Approval failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Approval failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
