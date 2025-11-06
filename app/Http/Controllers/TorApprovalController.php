<?php

namespace App\Http\Controllers;

use App\Models\TorGrade;
use App\Models\Advising;
use App\Models\Grade;
use App\Models\UserOtherInfo;
use App\Models\UploadedTor;
use App\Models\User;
use App\Notifications\TorRejectNotification;
use App\Notifications\TorStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\RemainingProgressService;

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
            'school_year' => 'required|string',
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
                    'status' => 'enrolled',
                ]);
            }

            /** ------------------------------------------------
             * ✅ 2. Save credited TOR grades
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
                    'school_year' => $validated['school_year'] ?? null,
                    'is_credited' => $g['is_credited'] ?? null,
                    'percent_grade' => $g['percent_grade'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->values();

            /** ------------------------------------------------
             * ✅ 2. Save or update credited TOR grades (no delete)
             * ------------------------------------------------ */
            $creditedGrades = collect($request->tor_grades)
                ->filter(fn($g) => $g['is_credited'] ?? false);

            foreach ($creditedGrades as $g) {
                TorGrade::updateOrCreate(
                    [
                        // Uniquely identify grade by tor_id + extracted_code or credited_code
                        'tor_id' => $validated['tor_id'],
                        'extracted_code' => $g['extracted_code'] ?? null,
                    ],
                    [
                        'user_id'       => $validated['user_id'],
                        'credited_id'   => $g['credited_id'] ?? null,
                        'credited_code' => $g['credited_code'] ?? null,
                        'title'         => $g['title'] ?? null,
                        'grade'         => $g['grade'] ?? null,
                        'credits'       => $g['credits'] ?? null,
                        'is_credited' => isset($g['is_credited']) ? intval($g['is_credited']) : 0,
                        'percent_grade' => $g['percent_grade'] ?? null,
                        'updated_at'    => now(),
                    ]
                );
            }


            /** ------------------------------------------------
             * ✅ 3. Mirror credited TOR grades into Grades table
             * ------------------------------------------------ */
            $gradesFromTOR = $creditedGrades->map(fn($g) => [
                'user_id'       => $validated['user_id'],
                'subject_id'    => $g['credited_id'] ?? null,
                'credited_id'   => $g['credited_id'] ?? null,
                'tor_grade_id'  => $g['tor_id'] ?? null,
                'advising_id'   => null,
                'type'          => 'credited',
                'status'        => 'done',
                'year_level'    => null,
                'grade'         => $g['grade'] ?? null,
                'grade_percent' => $g['percent_grade'] ?? null,
                'school_year' => $validated['school_year'] ?? null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            Grade::insert($gradesFromTOR->toArray());

            /** ------------------------------------------------
             * ✅ 4. Save advising subjects
             * ------------------------------------------------ */
            Advising::where('uploaded_tor_id', $validated['tor_id'])->delete();

            $advising = collect($request->advising)->map(fn($a) => [
                'user_id'         => $validated['user_id'],
                'uploaded_tor_id' => $validated['tor_id'],
                'subject_id'      => $a['subject_id'],
                'subject_code'    => $a['subject_code'],
                'semester'        => $a['semester'],
                'year_level'      => $a['year_level'],
                'school_year'      => $validated['school_year'],
                'subject_title'   => $a['subject_title'],
                'created_at'      => now(),
                'updated_at'      => now(),
            ])->values();

            Advising::insert($advising->toArray());

            /** ------------------------------------------------
             * ✅ 5. Mirror advising subjects into Grades table
             * ------------------------------------------------ */
            $gradesFromAdvising = $advising->map(fn($a) => [
                'user_id'       => $validated['user_id'],
                'subject_id'    => $a['subject_id'] ?? null,
                'credited_id'   => null,
                'tor_grade_id'  => $a['uploaded_tor_id'] ?? null,
                'advising_id'   => null,
                'type'          => 'advising',
                'status'        => 'enrolled',
                'year_level'    => $a['year_level'] ?? null,
                'grade'         => null,
                'grade_percent' => null,
                'school_year' => $validated['school_year'] ?? null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            Grade::insert($gradesFromAdvising->toArray());

            /** ------------------------------------------------
             * ✅ 6. Update UploadedTor status to "enrolled"
             * ------------------------------------------------ */

            $typeData = $this->computeStudentYearAndStatus($validated['user_id']);

            User::updateOrCreate(
                ['id' => $validated['user_id']],
                [
                    'year_level' => $typeData['year_level'],
                    'type' => $typeData['type'],
                ]
            );

            $uploadedTor = UploadedTor::find($validated['tor_id']);
            $uploadedTor->update(['status' => 'approved']);

            /** ------------------------------------------------
             * ✅ 7. Notify the user who uploaded the TOR
             * ------------------------------------------------ */
            $admin = auth('sanctum')->user();
            Log::error('admin account' . $admin);
            $uploader = User::find($uploadedTor->user_id);

            if ($uploader) {
                $uploader->notify(new TorStatusUpdatedNotification($uploadedTor, 'approved', $admin));
            }

            /** ------------------------------------------------
             * ✅ 8. Commit all changes
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

    private function computeStudentYearAndStatus($userId)
    {
        $user = User::with('otherInfo.course')->find($userId);
        if (!$user || !$user->otherInfo || !$user->otherInfo->course_id) {
            return ['year_level' => null, 'type' => 'unknown'];
        }

        $courseId = $user->otherInfo->course_id;
        $curriculum = \App\Models\Curriculum::where('course_id', $courseId)->first();

        if (!$curriculum) {
            return ['year_level' => null, 'type' => 'unknown'];
        }

        // Group subjects by year level
        $subjectsByYear = \App\Models\Subject::where('curriculum_id', $curriculum->id)
            ->get()
            ->groupBy('year_level');

        // All subject IDs student has already taken (done or enrolled)
        $studentSubjectIds = \App\Models\Grade::where('user_id', $userId)
            ->whereIn('status', ['done', 'enrolled'])
            ->pluck('subject_id')
            ->toArray();

        $yearLevels = [1, 2, 3, 4];
        $currentYearLevel = 1;

        foreach ($yearLevels as $year) {
            $subjects = $subjectsByYear[$year] ?? collect();
            $total = $subjects->count();
            $doneCount = $subjects->whereIn('id', $studentSubjectIds)->count();
            $remaining = $total - $doneCount;

            // If few subjects left in this year, advance to next
            if ($remaining <= 3 && $year < 4) {
                $currentYearLevel = $year + 1;
            } elseif ($remaining > 3) {
                $currentYearLevel = $year;
                break;
            }
        }

        // Check enrolled year levels to determine irregular/regular
        $enrolledYearLevels = \App\Models\Grade::where('user_id', $userId)
            ->whereIn('status', ['enrolled', 'done'])
            ->whereNotNull('year_level')
            ->pluck('year_level')
            ->unique()
            ->values()
            ->toArray();

        $type = count($enrolledYearLevels) > 1 ? 'irregular' : 'regular';

        // ✅ Update user table (since you said year_level and type are there)
        $user->update([
            'year_level' => $currentYearLevel,
            'type' => $type,
        ]);

        return [
            'year_level' => $currentYearLevel,
            'type' => $type,
        ];
    }



    public function rejectTor($torId)
    {
        try {
            // Find the TOR record
            $tor = UploadedTor::findOrFail($torId);

            // Update status to rejected
            $tor->update([
                'status' => 'rejected',
            ]);

            $admin = auth('sanctum')->user();
            $uploader = User::find($tor->user_id);

            if ($uploader) {
                $uploader->notify(new TorRejectNotification($tor, 'rejected', $admin));
            }

            // (Optional) Log or notify user
            // $tor->user->notify(new TorRejectedNotification($tor));

            return response()->json([
                'message' => 'TOR has been rejected successfully.',
                'data' => $tor
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Failed to reject TOR', [
                'error' => $e->getMessage(),
                'tor_id' => $torId,
            ]);

            return response()->json([
                'message' => 'Failed to reject TOR.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function computeRemainingProgress($torId, $curriculum_id)
    {
        set_time_limit(300);

        $tor = UploadedTor::findOrFail($torId);
        $apiKey = env('TESSERACT_KEY');
        $imageUrl = $tor->file_path;

        Log::info("🔍 Analyzing TOR ID: {$torId}");

        try {
            // Run your OCR + Advising + RemainingProgress logic
            // ✅ If you already have this in place, just call your logic here
            $remainingProgressService = new RemainingProgressService();
            $remainingProgress = $remainingProgressService->compute($tor, $curriculum_id);

            return response()->json([
                'message' => 'TOR analyzed successfully.',
                'tor_id' => $tor->id,
                'remaining_progress' => $remainingProgress,
            ]);
        } catch (\Exception $e) {
            Log::error("❌ TOR analysis failed: {$e->getMessage()}");

            return response()->json([
                'error' => 'Analysis failed.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
