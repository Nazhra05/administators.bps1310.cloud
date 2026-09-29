<?php
/**
 * generate-topics.php
 *
 * Run daily via cron. Calls Gemini API (with Google Search grounding) to find
 * 3 trending BPS-related topics phrased as questions, validates the output,
 * and writes it to public/trending-topics.json for the website's hero section
 * to fetch.
 *
 * Folder layout expected:
 *   administators.bps1310.cloud/
 *   ├── app/
 *   ├── config/
 *   ├── public/                  <- output JSON lands here
 *   └── scripts/                 <- this script + .env live here
 *
 * Usage: php generate-topics.php
 */

// ---- LOAD .env ----
$envFiles = [
    dirname(__DIR__) . '/.env',
    __DIR__ . '/.env',
];

foreach ($envFiles as $envPath) {
    if (file_exists($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!getenv($key)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
}

// ---- CONFIG ----
$GEMINI_API_KEY = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
$MODEL = 'gemini-3.8-flash';
$OUTPUT_FILE = __DIR__ . '/trending-topics.json';
$LOG_FILE = __DIR__ . '/generate-topics.log';

// Categories must match your curated image set on the frontend
$VALID_CATEGORIES = ['population', 'poverty-data', 'recruitment', 'economy', 'census', 'general'];

function log_msg($msg) {
    global $LOG_FILE;
    $line = '[' . date('Y-m-d H:i:s') . "] $msg\n";
    file_put_contents($LOG_FILE, $line, FILE_APPEND);
}

function fail_gracefully($reason) {
    log_msg("FAILED: $reason — keeping previous trending-topics.json unchanged.");
    exit(1);
}

if (!$GEMINI_API_KEY) {
    fail_gracefully('GEMINI_API_KEY not found — check .env file exists in scripts/ folder');
}

// ---- BUILD PROMPT ----
$prompt = <<<PROMPT
You are researching current trending topics related to Badan Pusat Statistik (BPS) Indonesia, especially relevant to BPS Solok Selatan (Kabupaten Solok Selatan). Use Google Search to find what people are actively searching for or asking about right now (news, recruitment, census activity, published statistics, public services).

Return EXACTLY 3 topics as a JSON array. Each topic must be phrased as a question that a member of the public would actually ask or search for. Each item must have these fields:
- "title": the topic written as a question in Bahasa Indonesia (max 10 words, must end with a question mark "?", natural and conversational, e.g. starting with "Berapa", "Bagaimana", "Kapan", "Apa", "Di mana", or "Mengapa")
- "copy": exactly 3 lines of explanatory copy in Bahasa Indonesia that answer or give context to the question, each line separated by "\\n", written for a general public audience
- "link": a URL to an official, relevant page (prioritize bps.go.id or solokselatankab.bps.go.id domains; only use other domains if no official page exists)
- "category": one of: population, poverty-data, recruitment, economy, census, general

Respond with ONLY the raw JSON array. No markdown formatting, no code fences, no explanation text before or after.
PROMPT;

// ---- CALL GEMINI API ----
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$MODEL}:generateContent?key={$GEMINI_API_KEY}";

$payload = [
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => $prompt]]]
    ],
    'tools' => [
        ['google_search' => new stdClass()] // enables search grounding
    ],
    'generationConfig' => [
        'temperature' => 0.4,
    ],
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 60,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    fail_gracefully("cURL error: $curlError");
}
if ($httpCode !== 200) {
    fail_gracefully("Gemini API returned HTTP $httpCode — response: " . substr($response, 0, 500));
}

$data = json_decode($response, true);
$rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$rawText) {
    fail_gracefully('No text content in Gemini response');
}

// Strip accidental markdown fences, just in case
$rawText = trim($rawText);
$rawText = preg_replace('/^```json\s*|\s*```$/m', '', $rawText);

$topics = json_decode($rawText, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    fail_gracefully('Failed to parse JSON from Gemini output: ' . json_last_error_msg());
}

if (!is_array($topics) || count($topics) !== 3) {
    fail_gracefully('Expected exactly 3 topics, got ' . (is_array($topics) ? count($topics) : 'non-array'));
}

// ---- VALIDATE EACH TOPIC ----
$validated = [];
foreach ($topics as $i => $topic) {
    $title = trim($topic['title'] ?? '');
    $copy = trim($topic['copy'] ?? '');
    $link = trim($topic['link'] ?? '');
    $category = trim($topic['category'] ?? '');

    if (!$title || !$copy || !$link) {
        fail_gracefully("Topic #$i missing required field(s)");
    }

    // Enforce question format: append "?" if the model forgot it
    if (substr($title, -1) !== '?') {
        $title .= '?';
    }

    if (!in_array($category, $VALID_CATEGORIES, true)) {
        log_msg("Topic #$i has unrecognized category '$category' — defaulting to 'general'");
        $category = 'general';
    }

    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        fail_gracefully("Topic #$i has invalid URL: $link");
    }

    // Check the link actually resolves (HEAD request, short timeout)
    $ch = curl_init($link);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    $linkStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($linkStatus < 200 || $linkStatus >= 400) {
        log_msg("Topic #$i link returned HTTP $linkStatus: $link — dropping this topic");
        continue; // skip this topic rather than fail the whole batch
    }

    $validated[] = [
        'title' => $title,
        'copy' => array_values(array_filter(explode("\n", $copy))), // array of 3 lines
        'link' => $link,
        'category' => $category,
    ];
}

if (count($validated) === 0) {
    fail_gracefully('All topics failed link validation — nothing to publish');
}

// ---- WRITE OUTPUT ----
$output = [
    'generated_at' => date('c'),
    'topics' => $validated,
];

$written = file_put_contents(
    $OUTPUT_FILE,
    json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

if ($written === false) {
    fail_gracefully("Could not write to $OUTPUT_FILE — check permissions");
}

log_msg('SUCCESS: wrote ' . count($validated) . ' topic(s) to ' . $OUTPUT_FILE);
echo "Done. Wrote " . count($validated) . " topics to $OUTPUT_FILE\n";