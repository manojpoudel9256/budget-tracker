<?php
require 'db_connect.php'; // To get the API key constant

header('Content-Type: application/json');

$api_key = GEMINI_API_KEY;
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=$api_key";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
} else {
    echo $result;
}
curl_close($ch);
?>