<?php
// Suppress PHP errors from breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

require 'session_check.php';
require 'db_connect.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not logged in");
    }

    $user_id = $_SESSION['user_id'];
    $force_refresh = isset($_GET['refresh']) && $_GET['refresh'] == 'true';

    // 1. Check Cache (One insight per 24 hours unless forced)
    $stmt = $pdo->prepare("SELECT * FROM ai_insights WHERE user_id = ? AND created_at >= NOW() - INTERVAL 24 HOUR ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $cached = $stmt->fetch();

    if ($cached && !$force_refresh) {
        $cached_json = json_decode($cached['insight_text'], true);

        // Check if cached language matches current session language
        $cached_lang = $cached_json['lang'] ?? 'en'; // Default to en if missing
        $current_lang = $_SESSION['lang'] ?? 'en';

        if ($cached_lang === $current_lang) {
            echo json_encode([
                'success' => true,
                'source' => 'cache',
                'insight' => $cached['insight_text'],
                'type' => $cached['type']
            ]);
            exit;
        }
        // If language mismatch, ignore cache and proceed to generate new one
    }

    // 2. Fetch Financial Context (Last 30 Days)
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $stmt = $pdo->prepare("SELECT type, category, amount, date FROM transactions WHERE user_id = ? AND date >= ? ORDER BY date DESC");
    $stmt->execute([$user_id, $start_date]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($transactions) < 5) {
        $user_lang = $_SESSION['lang'] ?? 'en';
        $fallback_summary = ($user_lang == 'jp') ? '記録を開始しましょう！' : 'Start Tracking!';
        $fallback_detail = ($user_lang == 'jp')
            ? "分析するにはもう少しデータが必要です！毎日の取引を追加し続けると、すぐにパーソナライズされた財務レポートを作成します。"
            : "I need a bit more data to analyze! Keep adding your daily transactions, and I'll generate a personalized financial report for you soon.";

        echo json_encode([
            'success' => true,
            'source' => 'system',
            'insight' => json_encode([
                'summary' => $fallback_summary,
                'detail' => $fallback_detail,
                'type' => 'tip',
                'lang' => $user_lang // Add language tag to system fallback
            ]),
            'type' => 'neutral'
        ]);
        exit;
    }

    // 3. Prepare Prompt
    $csv_data = "Date,Type,Category,Amount\n";
    foreach ($transactions as $t) {
        $csv_data .= "{$t['date']},{$t['type']},{$t['category']},{$t['amount']}\n";
    }

    $user_lang = $_SESSION['lang'] ?? 'en';
    $user_currency = $_SESSION['currency'] ?? 'USD';
    $lang_instruction = ($user_lang == 'jp') ? "Respond in Japanese (日本語)." : "Respond in English.";

    $prompt = "You are a friendly, witty financial assistant. Analyze this transaction data (last 30 days).
    The user's currency is $user_currency. Always use this currency symbol (e.g. $, ¥, €) when mentioning money.
    Identify one key pattern, saving opportunity, or praise. $lang_instruction
    
    Output JSON ONLY in this format:
    {
        \"summary\": \"A short, punchy 5-7 word headline (e.g., 'Dining Out is High!')\",
        \"detail\": \"A friendly 1-2 sentence explanation with a specific number or tip. Use emojis.\",
        \"type\": \"warning|praise|tip\"
    }

    Data:
    $csv_data";

    // 4. Call Gemini API using cURL (Robust & SSL options)
    $api_key = GEMINI_API_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=$api_key";

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    // Disable SSL verification for development environments (XAMPP common issue)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('Gemini API Error: ' . curl_error($ch));
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code !== 200) {
        $error_response = json_decode($result, true);
        $error_msg = $error_response['error']['message'] ?? "HTTP Error $http_code";

        // Handle specific 503 Service Unavailable (Overloaded)
        if ($http_code === 503) {
            throw new Exception("The AI is currently experiencing high traffic. Please try again in 1 minute.");
        }

        // Clean up error message for display
        throw new Exception("AI Error ($http_code): " . substr($error_msg, 0, 100));
    }

    curl_close($ch);

    $response = json_decode($result, true);
    $ai_text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

    // Extract JSON from markdown code block if present
    if (preg_match('/```json\s*(.*?)\s*```/s', $ai_text, $matches)) {
        $clean_json = $matches[1];
    } else {
        $clean_json = $ai_text;
    }

    $insight_data = json_decode($clean_json, true);

    // Fallback if JSON decode fails
    if (!$insight_data) {
        // Try to recover if it's just raw text
        $user_lang = $_SESSION['lang'] ?? 'en';
        $fallback_summary = ($user_lang == 'jp') ? '財務アップデート' : 'Financial Update';
        $fallback_detail = strip_tags($ai_text) ?: (($user_lang == 'jp') ? '支出を分析しました。記録を続けましょう！' : 'I analyzed your spending. Keep tracking!');

        $insight_data = [
            'summary' => $fallback_summary,
            'detail' => $fallback_detail,
            'type' => 'tip'
        ];
    }

    // 5. Cache Result
    // Add Language Tag to JSON before saving
    $insight_data['lang'] = $_SESSION['lang'] ?? 'en';

    // We store the whole JSON object string in insight_text
    $json_string = json_encode($insight_data);

    // Force type to be one of the expected values
    $valid_types = ['warning', 'praise', 'tip'];
    $db_type = in_array($insight_data['type'] ?? '', $valid_types) ? $insight_data['type'] : 'tip';

    $stmt = $pdo->prepare("INSERT INTO ai_insights (user_id, insight_text, type) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $json_string, $db_type]);

    echo json_encode([
        'success' => true,
        'source' => 'api',
        'insight' => $json_string,
        'type' => $db_type
    ]);

} catch (Exception $e) {
    // Return error as JSON, not HTML
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>