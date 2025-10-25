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
