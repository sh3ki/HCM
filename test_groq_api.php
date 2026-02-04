<?php
require __DIR__ . '/config/groq.php';

echo "Testing Groq Configuration:\n";
echo "✓ API Key: " . substr(GROQ_API_KEY, 0, 20) . "...\n";
echo "✓ Model: " . GROQ_MODEL . "\n";
echo "✓ Max Tokens: " . GROQ_MAX_TOKENS . "\n\n";
echo "Testing API connection...\n";

$data = json_encode([
    'model' => GROQ_MODEL,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful HCM assistant.'],
        ['role' => 'user', 'content' => 'Say hello and confirm you can help with: 1) general questions, 2) math (like calculate 15% of 50000), 3) HCM system navigation']
    ],
    'max_tokens' => 150,
    'temperature' => 0.7
]);

$ch = curl_init(GROQ_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . GROQ_API_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "\nHTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "✅ SUCCESS! Groq API is working!\n\n";
    echo "AI Response:\n";
    echo $result['choices'][0]['message']['content'] . "\n\n";
    echo "🎉 Your chatbot is READY TO USE!\n";
    echo "👉 Open: http://localhost/HCM/test_chatbot.php\n";
} else {
    echo "❌ ERROR (HTTP $httpCode)\n";
    if ($error) {
        echo "Curl Error: $error\n";
    }
    echo "Response: $response\n";
}
