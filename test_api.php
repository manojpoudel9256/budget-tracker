<?php
require 'config.php'; // Load API key

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . GEMINI_API_KEY;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for local SSL issues

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h1>Gemini Model Check</h1>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($curlError) {
    echo "<p><strong>Curl Error:</strong> $curlError</p>";
}

echo "<h2>Available Models:</h2>";
$data = json_decode($response, true);

if (isset($data['models'])) {
    echo "<ul>";
    foreach ($data['models'] as $model) {
        // Check if generateContent is supported
        $isSupported = false;
        foreach ($model['supportedGenerationMethods'] as $method) {
            if ($method === 'generateContent') {
                $isSupported = true;
                break;
            }
        }

        if ($isSupported) {
            echo "<li><strong>" . $model['name'] . "</strong></li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>No models found or error accessing API.</p>";
}

echo "<h2>Full Response:</h2>";
echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
?>