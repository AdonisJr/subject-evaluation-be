<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\UploadedTor;
use App\Models\Subject;
use App\Models\TorGrade;

class TesseractOcrController extends Controller
{
    public function analyzeTor($torId, $curriculum_id)
    {
        set_time_limit(300);

        $tor = UploadedTor::findOrFail($torId);
        $apiKey = env('TESSERACT_KEY');
        $imageUrl = $tor->file_path;

        Log::info("🟢 Starting OCR + Advising for TOR ID: {$torId}");
        Log::info("🌐 File: {$imageUrl}");

        try {
            // 🧠 Step 1. Send OCR request
            $response = Http::timeout(300)
                ->retry(2, 5000)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => 'google/gemma-3-4b-it:free',
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Perform OCR: extract all readable text from this image or PDF accurately. 
Return JSON array only in this format: 
[{\"code\":\"\",\"title\":\"\",\"grade\":\"\",\"credits\":0}]"
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => ['url' => $imageUrl]
                            ]
                        ]
                    ]]
                ]);

            if ($response->failed()) {
                Log::error("❌ OCR request failed: " . $response->body());
                return response()->json([
                    'error' => 'OCR request failed',
                    'details' => $response->body()
                ], 500);
            }

            // 🧹 Step 2. Parse OCR response
            $result = $response->json();
            $rawText = $result['choices'][0]['message']['content'] ?? '';
            $cleaned = preg_replace('/^```json|```$/m', '', trim($rawText));
            $cleaned = trim($cleaned);
            $jsonData = json_decode($cleaned, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("⚠️ Failed to parse OCR JSON");
                $tor->update(['status' => 'failed', 'remarks' => 'Failed to parse OCR JSON.']);
                return response()->json([
                    'tor_id' => $torId,
                    'file_path' => $imageUrl,
                    'raw_text' => $cleaned
                ]);
            }

            Log::info("✅ OCR parsed successfully");

            // 🧩 Step 3. Clean and match OCR subjects with curriculum
            $subjects = Subject::where('curriculum_id', $curriculum_id)
                ->get()
                ->keyBy(fn($item) => strtolower(str_replace(' ', '', $item->code)));

            $records = collect($jsonData)->map(function ($record) use ($subjects) {
                $recordCode = strtolower(str_replace(' ', '', $record['code'] ?? ''));

                if (isset($subjects[$recordCode])) {
                    $subject = $subjects[$recordCode];
                    $record['subject_id'] = $subject->id;
                    $record['is_credited'] = true;
                    $record['credited_code'] = $subject->code;
                } else {
                    $record['subject_id'] = null;
                    $record['is_credited'] = false;
                    $record['credited_code'] = $record['code'] ?? '';
                }

                // Clean code (remove spaces + uppercase)
                $record['code'] = strtoupper(str_replace(' ', '', $record['code'] ?? ''));

                return $record;
            });

            // 💾 Step 4. Save results to tor_grades
            foreach ($records as $rec) {
                TorGrade::create([
                    'tor_id'        => $tor->id,
                    'user_id'       => $tor->user_id,
                    'subject_id'    => $rec['subject_id'],
                    'credited_code' => $rec['credited_code'],
                    'title'         => $rec['title'] ?? '',
                    'grade'         => $rec['grade'] ?? null,
                    'credits'       => $rec['credits'] ?? 0,
                ]);
            }

            // ----------------------------
            // 🧮 Step 5. Advising Logic (per your requested format)
            // ----------------------------

            // Passing subjects (already credited/passed)
            $passed = TorGrade::where('user_id', $tor->user_id)
                ->where('grade', '>=', 75)
                ->pluck('subject_id')
                ->toArray();

            // Get curriculum subjects split by semester (include all years, ordered by year)
            $firstSemAll = Subject::where('curriculum_id', $curriculum_id)
                ->where('semester', 1)
                ->with('prerequisites')
                ->orderBy('year_level')
                ->orderBy('id')
                ->get();

            $secondSemAll = Subject::where('curriculum_id', $curriculum_id)
                ->where('semester', 2)
                ->with('prerequisites')
                ->orderBy('year_level')
                ->orderBy('id')
                ->get();

            // helper to compute eligible subjects for one semester list
            $computeEligible = function ($subjectsList, $passedSubjects, $unitCap = 24) {
                $eligible = collect();
                $total = 0;
                $usedPrereqChains = [];

                foreach ($subjectsList as $subj) {
                    // skip if already passed/credited
                    if (in_array($subj->id, $passedSubjects)) continue;

                    // get prerequisite ids
                    $prereqIds = $subj->prerequisites->pluck('prerequisite_id')->toArray();

                    // only include if all prerequisites are already passed
                    $allPassed = collect($prereqIds)->every(fn($id) => in_array($id, $passedSubjects));
                    if (!$allPassed) continue;

                    // apply "one subject per prerequisite chain" only when prerequisites exist
                    if (!empty($prereqIds)) {
                        $chainKey = implode('-', $prereqIds);
                        if (isset($usedPrereqChains[$chainKey])) {
                            continue;
                        }
                        $usedPrereqChains[$chainKey] = true;
                    }

                    // respect unit cap for this semester
                    $units = $subj->units ?? 0;
                    if ($total + $units <= $unitCap) {
                        $eligible->push($subj);
                        $total += $units;
                    } else {
                        // if adding this subject exceeds the semester cap, skip further subjects
                        // (we break to keep earliest-by-year order consistent)
                        break;
                    }
                }

                // map to minimal array for JSON response
                $subjectsArray = $eligible->map(fn($s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'title' => $s->name,
                    'units' => $s->units,
                    'year_level' => $s->year_level,
                    'semester' => $s->semester
                ])->values();

                return [
                    'subjects' => $subjectsArray,
                    'total_units' => $total
                ];
            };

            $firstResult = $computeEligible($firstSemAll, $passed, 27);
            $secondResult = $computeEligible($secondSemAll, $passed, 27);

            // 🟢 Step 6. Update TOR status
            $tor->update([
                'status' => 'submitted',
                'remarks' => 'OCR and advising completed successfully.'
            ]);

            Log::info("✅ TOR analysis + advising complete for ID {$torId}");

            // 🧾 Step 7. Return Response — as you requested
            return response()->json([
                'message' => 'TOR analyzed and advising generated successfully.',
                'tor_id' => $tor->id,
                'ocr_records' => $records,
                'advising' => [
                    'first_sem' => $firstResult['subjects'],
                    'first_sem_total_units' => $firstResult['total_units'],
                    'second_sem' => $secondResult['subjects'],
                    'second_sem_total_units' => $secondResult['total_units'],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("🔥 OCR error for TOR {$torId}: " . $e->getMessage());
            $tor->update(['status' => 'failed', 'remarks' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
