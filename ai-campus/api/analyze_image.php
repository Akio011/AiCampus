<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']); exit;
}
header('Content-Type: application/json');

if (empty($_FILES['image']['tmp_name'])) {
    echo json_encode(['error' => 'No image uploaded']); exit;
}

$imageData = base64_encode(file_get_contents($_FILES['image']['tmp_name']));

$payload = json_encode([
    'model'  => 'llava',
    'stream' => false,
    'messages' => [[
        'role'    => 'user',
        'content' => 'Look at this image of a lost or found item. Reply with ONLY a JSON object with two keys: "title" (short item name, max 6 words) and "description" (one sentence describing the item including color, type, and any notable features). Example: {"title":"Blue Spiral Notebook","description":"A blue spiral-bound college-ruled notebook with handwritten notes inside."}',
        'images'  => [$imageData]
    ]]
]);

$ch = curl_init('http://localhost:11434/api/chat');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 60,
]);
$response = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['error' => 'Ollama not reachable: ' . $curlErr]); exit;
}

$data = json_decode($response, true);
$content = $data['message']['content'] ?? '';

// Extract JSON from the response
preg_match('/\{.*\}/s', $content, $matches);
if ($matches) {
    $result = json_decode($matches[0], true);
    if ($result && isset($result['title'])) {
        echo json_encode(['title' => $result['title'], 'description' => $result['description'] ?? '']);
        exit;
    }
}

echo json_encode(['error' => 'Could not parse response', 'raw' => $content]);
