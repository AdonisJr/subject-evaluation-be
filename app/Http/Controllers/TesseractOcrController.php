<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\UploadedTor;
use App\Models\Subject;
use App\Models\TorGrade;
use App\Models\User;
use App\Notifications\TorSubmittedNotification;
use App\Services\RemainingProgressService;
use Illuminate\Support\Str;


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
                // Normalize OCR data
                $recordCode = strtolower(str_replace(' ', '', $record['code'] ?? ''));
                $recordTitle = strtolower(preg_replace('/\s+/', ' ', trim($record['title'] ?? '')));

                $matchType = 'none'; // For debugging
                $matchedSubject = null;

                foreach ($subjects as $subject) {
                    $subjCode = strtolower(str_replace(' ', '', $subject->code));
                    $subjTitle = strtolower(preg_replace('/\s+/', ' ', trim($subject->name)));

                    // ✅ Exact code match
                    if ($recordCode === $subjCode) {
                        $matchType = 'exact_code';
                        $matchedSubject = $subject;
                        break;
                    }

                    // ✅ Partial code (NSTP1 vs NSTP1A)
                    if (Str::startsWith($recordCode, $subjCode) || Str::startsWith($subjCode, $recordCode)) {
                        $matchType = 'partial_code';
                        $matchedSubject = $subject;
                        break;
                    }

                    // ✅ Fuzzy code similarity (≥90%)
                    similar_text($recordCode, $subjCode, $codePercent);
                    if ($codePercent >= 90) {
                        $matchType = 'fuzzy_code';
                        $matchedSubject = $subject;
                        break;
                    }

                    // ✅ Fuzzy title similarity (≥85%)
                    similar_text($recordTitle, $subjTitle, $titlePercent);
                    if ($titlePercent >= 85) {
                        $matchType = 'fuzzy_title';
                        $matchedSubject = $subject;
                        break;
                    }
                }

                if ($matchedSubject) {
                    $record['credited_id'] = $matchedSubject->id;
                    $record['is_credited'] = true;
                    $record['credited_code'] = $matchedSubject->code;
                    Log::info("✅ Matched '{$record['code']}' ({$record['title']}) → {$matchedSubject->code} ({$matchedSubject->name}) via {$matchType}");
                } else {
                    $record['credited_id'] = null;
                    $record['is_credited'] = false;
                    $record['credited_code'] = null;
                    Log::warning("⚠️ No match for '{$record['code']}' ({$record['title']})");
                }

                // Normalize for display
                $record['code'] = strtoupper(str_replace(' ', '', $record['code'] ?? ''));

                return $record;
            });


            // 💾 Step 4. Save results to tor_grades (with grade conversion + percent grade)
            $records = $records->map(function ($rec) use ($tor) {
                $rawGrade = $rec['grade'] ?? null;
                $convertedGrade = null;
                $percentGrade = null;

                if (is_numeric($rawGrade)) {
                    $gradeValue = floatval($rawGrade);
                    $percentGrade = $gradeValue; // original percent

                    // 🔁 Convert % grade (if >5 means percent)
                    if ($gradeValue > 5) {
                        if ($gradeValue >= 97) $convertedGrade = 1.00;
                        elseif ($gradeValue >= 94) $convertedGrade = 1.25;
                        elseif ($gradeValue >= 91) $convertedGrade = 1.50;
                        elseif ($gradeValue >= 88) $convertedGrade = 1.75;
                        elseif ($gradeValue >= 85) $convertedGrade = 2.00;
                        elseif ($gradeValue >= 82) $convertedGrade = 2.25;
                        elseif ($gradeValue >= 79) $convertedGrade = 2.50;
                        elseif ($gradeValue >= 76) $convertedGrade = 2.75;
                        elseif ($gradeValue >= 75) $convertedGrade = 3.00;
                        else $convertedGrade = 5.00;
                    } else {
                        $convertedGrade = $gradeValue; // already 1–5 scale
                    }
                }

                Log::info('🧩 Creating TorGrade record', [
                    'grade' => $convertedGrade,
                    'type' => gettype($convertedGrade),
                ]);

                Log::info('🧩 Creating TorGrade record', [
                    'is_credited' => $rec['is_credited'],
                    'type' => gettype($rec['is_credited']),
                ]);

                // Save to database
                TorGrade::create([
                    'tor_id'         => $tor->id,
                    'user_id'        => $tor->user_id,
                    'extracted_code' => $rec['code'] ?? null,
                    'credited_id'    => $rec['credited_id'] ?? null,
                    'credited_code'  => $rec['credited_code'] ?? null,
                    'is_credited'    => $rec['is_credited'] ? 1 : 0,
                    'title'          => $rec['title'] ?? '',
                    'grade'          => $convertedGrade,
                    'percent_grade'  => $percentGrade,
                    'credits'        => $rec['credits'] ?? 0,
                ]);


                // Update record for response
                $rec['grade'] = $convertedGrade;
                $rec['percent_grade'] = $percentGrade;

                return $rec;
            });


            // ----------------------------
            // 🧮 Step 5. Advising Logic (first_sem & second_sem)
            // ----------------------------

            // Passed subjects based on 1.00–3.00 scale
            $passed = TorGrade::where('user_id', $tor->user_id)
                ->whereBetween('grade', [1.00, 3.00])
                ->pluck('credited_id')
                ->toArray();

            // Get subjects by semester
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

            // helper function for filtering eligible subjects
            $computeEligible = function ($subjectsList, $passedSubjects, $unitCap = 27) {
                $eligible = collect();
                $total = 0;

                foreach ($subjectsList as $subj) {
                    if (in_array($subj->id, $passedSubjects)) continue;

                    $prereqIds = $subj->prerequisites->pluck('prerequisite_id')->toArray();
                    $allPassed = collect($prereqIds)->every(fn($id) => in_array($id, $passedSubjects));

                    if (!$allPassed) continue;

                    $units = $subj->units ?? 0;
                    if ($total + $units <= $unitCap) {
                        $eligible->push($subj);
                        $total += $units;
                    } else break;
                }

                $subjectsArray = $eligible->map(function ($s) {
                    $prereq = $s->prerequisites->first();
                    $prereqSubject = $prereq && $prereq->prerequisite_id
                        ? Subject::find($prereq->prerequisite_id)
                        : null;

                    return [
                        'subject_id' => $s->id, // ✅ Added this
                        'code' => $s->code,
                        'title' => $s->name,
                        'units' => $s->units,
                        'year_level' => $s->year_level,
                        'semester' => $s->semester,
                        'prerequisite' => $prereqSubject?->code ?? null,
                    ];
                })->values();

                return [
                    'subjects' => $subjectsArray,
                    'total_units' => $total
                ];
            };

            $firstResult = $computeEligible($firstSemAll, $passed, 27);
            $secondResult = $computeEligible($secondSemAll, $passed, 27);

            // 🟢 Step 6. Update TOR status
            // $tor->update([
            //     'status' => 'submitted',
            //     'remarks' => 'OCR and advising completed successfully.'
            // ]);

            Log::info("✅ TOR analysis + advising complete for ID {$torId}");

            // ✅ Notify all admins that this TOR was successfully submitted
            // $user = $tor->user; // define user based on the uploaded TOR record

            // if ($user) {
            //     $admins = User::where('role', 'admin')->get();

            //     foreach ($admins as $admin) {
            //         $admin->notify(new TorSubmittedNotification($tor, $user));
            //     }

            //     Log::info("📨 Notified all admins about TOR ID {$torId} from {$user->email}");
            // } else {
            //     Log::warning("⚠️ No user found for TOR ID {$torId} — skipping admin notification");
            // }

            // 🧮 Step 7. Compute remaining progress
            // $remainingProgress = $this->remainingProgressService->compute($tor, $curriculum_id);
            $remainingProgressService = new RemainingProgressService();
            // ... later:
            $remainingProgress = $remainingProgressService->compute($tor, $curriculum_id);

            // 🧾 Step 7. Return Response
            return response()->json([
                'message' => 'TOR analyzed and advising generated successfully.',
                'tor_id' => $tor->id,
                'ocr_records' => $records,
                'advising' => [
                    'first_sem' => $firstResult['subjects'],
                    'first_sem_total_units' => $firstResult['total_units'],
                    'second_sem' => $secondResult['subjects'],
                    'second_sem_total_units' => $secondResult['total_units'],
                ],
                'remaining_progress' => $remainingProgress
            ]);
        } catch (\Exception $e) {
            Log::error("🔥 OCR error for TOR {$torId}: " . $e->getMessage());
            $tor->update(['status' => 'failed', 'remarks' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
