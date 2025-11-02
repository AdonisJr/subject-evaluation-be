<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatController extends Controller
{
    public function summary(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // ✅ Load user's course via relation
        $user->load('otherInfo.course');

        $courseId = $user->otherInfo->course_id ?? null;

        if (!$courseId) {
            return response()->json(['message' => 'No course assigned to this user'], 400);
        }

        // ✅ Find the curriculum for this course
        $curriculum = \App\Models\Curriculum::where('course_id', $courseId)->first();

        if (!$curriculum) {
            return response()->json(['message' => 'No curriculum found for this course'], 404);
        }

        // ✅ Count total subjects for the curriculum
        $totalSubjects = \App\Models\Subject::where('curriculum_id', $curriculum->id)->count();

        // ✅ Count done (credited) subjects
        $doneSubjects = \App\Models\Grade::where('user_id', $user->id)
            ->where('status', 'done')
            ->count();

        // ✅ Count currently enrolled subjects
        $enrolledSubjects = \App\Models\Grade::where('user_id', $user->id)
            ->where('status', 'enrolled')
            ->count();

        // ✅ Compute remaining semesters
        $subjectsPerSem = 7; // adjust depending on your curriculum
        $remainingSubjects = max(0, $totalSubjects - $doneSubjects);
        $remainingSemesters = ceil($remainingSubjects / $subjectsPerSem);

        return response()->json([
            'done_subjects' => $doneSubjects,
            'enrolled_subjects' => $enrolledSubjects,
            'total_subjects' => $totalSubjects,
            'remaining_semesters' => $remainingSemesters,
        ]);
    }

    public function adminSummary()
    {
        // LEFT JOIN users with other_infos
        $query = DB::table('users')
            ->leftJoin('user_other_infos', 'users.id', '=', 'user_other_infos.user_id')
            ->select(
                'users.id',
                'user_other_infos.status',
                'user_other_infos.category'
            );

        // Count categories
        $totalEnrolled = (clone $query)->where('user_other_infos.status', 'enrolled')->count();
        $totalPending = (clone $query)->whereNull('user_other_infos.status')->count();
        $totalTransferee = (clone $query)->where('user_other_infos.category', 'transferee')->count();
        $totalShiftee = (clone $query)->where('user_other_infos.category', 'shiftee')->count();
        $totalNew = (clone $query)->where('user_other_infos.category', 'new')->count();

        // Count users without any other_info record
        $noOtherInfo = DB::table('users')
            ->leftJoin('user_other_infos', 'users.id', '=', 'user_other_infos.user_id')
            ->whereNull('user_other_infos.user_id')
            ->count();

        return response()->json([
            'enrolled' => $totalEnrolled,
            'pending' => $totalPending + $noOtherInfo, // include those with no record
            'transferee' => $totalTransferee,
            'shiftee' => $totalShiftee,
            'new' => $totalNew,
        ]);
    }
}
