<?php
require_once __DIR__ . '/app/autoload.php';
use App\Core\Env;

// Load environment
Env::load(__DIR__ . '/.env');

$key = getenv('OPENAI_API_KEY');

if (!$key) {
    echo "ERROR: OPENAI_API_KEY no encontrada en las variables de entorno.\n";
    exit(1);
}

echo "INFO: Clave encontrada (empieza por " . substr($key, 0, 15) . "...)\n";

// Test the key with a lightweight API call (List Models)
// This confirms the key is valid without spending much (listing models is usually free/cheap)
$ch = curl_init('https://api.openai.com/v1/models');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $key
]);
// Set a timeout to avoid hanging
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// Verify SSL (should be default true, but ensuring)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    echo "SUCCESS: La clave es válida. OpenAI API respondió correctamente.\n";
    $data = json_decode($response, true);
    if (isset($data['data'])) {
        echo "Conexión establecida. Modelos disponibles confirmados.\n";
    }
} else {
    echo "ERROR: La clave parece inválida o hay un problema de conexión.\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . substr($response, 0, 200) . "...\n";
    if ($error) echo "Curl Error: $error\n";
}