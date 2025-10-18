<?php

namespace App\Http\Controllers;

use App\Models\Advising;
use App\Models\UploadedTor;
use App\Models\TorGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdvisingController extends Controller
{
    /**
     * Save generated advising subjects
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tor_id' => 'required|exists:uploaded_tors,id',
            'advising' => 'required|array',
            'advising.first_sem' => 'array',
            'advising.second_sem' => 'array',
            'ocr_records' => 'required|array'
        ]);

        $user = auth('sanctum')->user();
        $tor = UploadedTor::findOrFail($validated['tor_id']);

        DB::beginTransaction();
        try {
            // ✅ Delete previous advising
            Advising::where('uploaded_tor_id', $tor->id)->delete();

            // ✅ Save new advising
            $advisingRecords = [];
            foreach (['first_sem', 'second_sem'] as $sem) {
                foreach ($validated['advising'][$sem] ?? [] as $subject) {
                    $advisingRecords[] = [
                        'uploaded_tor_id' => $tor->id,
                        'user_id' => $user->id,
                        'semester' => $sem,
                        'year_level' => $subject['year_level'] ?? null,
                        'subject_code' => $subject['code'] ?? '',
                        'subject_title' => $subject['title'] ?? '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            Advising::insert($advisingRecords);

            // ✅ Save OCR records (use `tor_id`, not `uploaded_tor_id`)
            TorGrade::where('tor_id', $tor->id)->delete();
            $ocrRecords = collect($validated['ocr_records'])->map(function ($r) use ($tor, $user) {
                return [
                    'tor_id' => $tor->id, // ✅ fixed
                    'user_id' => $user->id,
                    'extracted_code' => $r['code'] ?? '',
                    'subject_id' => $r['subject_id'] ?? null,
                    'subject_code' => $r['credited_code'] ?? null,
                    'title' => $r['title'] ?? '',
                    'credits' => $r['credits'] ?? 0,
                    'grade' => $r['grade'] ?? '',
                    'is_credited' => $r['is_credited'] ?? false,
                    'percent_grade' => $r['percent_grade'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            TorGrade::insert($ocrRecords);

            DB::commit();

            return response()->json([
                'message' => 'Advising and OCR records saved successfully.',
                'advising_count' => count($advisingRecords),
                'ocr_count' => count($ocrRecords),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save advising', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to save advising.', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Retrieve advising for a specific TOR
     */
    public function show($torId)
    {
        $advising = Advising::where('uploaded_tor_id', $torId)
            ->select('semester', 'subject_code', 'subject_title', 'units')
            ->get()
            ->groupBy('semester');

        return response()->json($advising);
    }
}
