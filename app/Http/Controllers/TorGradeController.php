<?php

namespace App\Http\Controllers;

use App\Models\TorGrade;
use App\Models\UploadedTor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TorGradeController extends Controller
{
    /**
     * Store or update multiple checked OCR records for a TOR and update TOR status.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tor_id' => 'required|exists:uploaded_tors,id',
            'grades' => 'required|array',
            'grades.*.credited_id' => 'nullable|exists:subjects,id',
            'grades.*.code' => 'required|string',
            'grades.*.title' => 'required|string',
            'grades.*.credits' => 'required|numeric',
            'grades.*.grade' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $tor = UploadedTor::findOrFail($request->tor_id);

            $saved = [];
            foreach ($request->grades as $g) {
                $record = TorGrade::updateOrCreate(
                    [
                        'tor_id' => $tor->id,
                        'user_id' => $tor->user_id,
                        'subject_id' => $g['credited_id'] ?? null,
                    ],
                    [
                        'credited_code' => $g['code'],
                        'title' => $g['title'],
                        'credits' => $g['credits'],
                        'grade' => $g['grade'] ?? null,
                    ]
                );

                $saved[] = $record;
            }

            // Update TOR status to 'processing'
            $tor->update(['status' => 'processing']);

            DB::commit();

            return response()->json([
                'message' => 'Grades saved and TOR status updated successfully!',
                'data' => $saved,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to save TOR grades or update TOR: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to save grades or update TOR status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch all grades for a TOR.
     */
    public function index($torId)
    {
        $grades = TorGrade::with('subject')
            ->where('tor_id', $torId)
            ->get();

        return response()->json($grades);
    }
}
