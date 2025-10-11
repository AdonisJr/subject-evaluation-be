<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\UploadedTor;
use App\Models\Subject;

class TesseractOcrController extends Controller
{
    public function analyzeTor($torId, $curriculum_id)
    {
        set_time_limit(300);

        $tor = UploadedTor::findOrFail($torId);
        $apiKey = env('TESSERACT_KEY');
        $imageUrl = $tor->file_path;

        Log::info("🟢 Starting OpenRouter OCR for TOR ID: {$torId}");
        Log::info("🌐 Image URL: {$imageUrl}");
        Log::info("🔑 API Key present: ✅ Yes");

        try {
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
                Log::error("❌ OpenRouter request failed: " . $response->body());
                return response()->json([
                    'error' => 'OpenRouter request failed',
                    'details' => $response->body()
                ], 500);
            }

            $result = $response->json();
            $rawText = $result['choices'][0]['message']['content'] ?? '';

            $cleaned = preg_replace('/^```json|```$/m', '', trim($rawText));
            $cleaned = trim($cleaned);

            $jsonData = json_decode($cleaned, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("⚠️ Failed to parse JSON. Returning raw text instead.");
                return response()->json([
                    'tor_id' => $torId,
                    'file_path' => $imageUrl,
                    'raw_text' => $cleaned
                ]);
            }

            Log::info("✅ Parsed OCR JSON successfully.");

            $subjects = Subject::where('curriculum_id', $curriculum_id)
                ->get()
                ->keyBy(function ($item) {
                    return strtolower($item->code);
                });

            // Add is_credited field to each OCR record
            $records = array_map(function ($record) use ($subjects) {
                $recordCode = strtolower($record['code'] ?? '');

                if (isset($subjects[$recordCode])) {
                    $subject = $subjects[$recordCode];
                    $record['is_credited'] = true;
                    $record['credited_id'] = $subject->id;
                    $record['credited_code'] = $subject->code;
                } else {
                    $record['is_credited'] = false;
                    $record['credited_id'] = null;
                    $record['credited_code'] = null;
                }

                return $record;
            }, $jsonData);

            return response()->json([
                'tor_id' => $torId,
                'file_path' => $imageUrl,
                'records' => $records,
            ]);
        } catch (\Exception $e) {
            Log::error("🔥 OCR error for TOR {$torId}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
