<?php
require_once __DIR__ . '/../config/auth.php';

verificarSesion(['admin']);

header('Content-Type: application/json');

function json_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido', 405);
}

// Parámetros de chunk
$uploadId    = trim($_POST['uploadId'] ?? '');
$fileName    = trim($_POST['fileName'] ?? '');
$chunkIndex  = isset($_POST['chunkIndex']) ? intval($_POST['chunkIndex']) : null;
$totalChunks = isset($_POST['totalChunks']) ? intval($_POST['totalChunks']) : null;

if ($uploadId === '' || $fileName === '' || $chunkIndex === null || $totalChunks === null) {
    json_error('Parámetros de subida incompletos');
}

// Directorios
$baseUploads = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
if (!$baseUploads || !is_dir($baseUploads)) {
    $baseUploads = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($baseUploads)) { @mkdir($baseUploads, 0777, true); }
}
$tmpBase = $baseUploads . DIRECTORY_SEPARATOR . 'tmp_evasion';
if (!is_dir($tmpBase)) { @mkdir($tmpBase, 0777, true); }
$tmpDir = $tmpBase . DIRECTORY_SEPARATOR . preg_replace('/[^A-Za-z0-9._-]/', '_', $uploadId);
if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0777, true); }

// Guardar chunk
if (!isset($_FILES['chunk']) || !is_uploaded_file($_FILES['chunk']['tmp_name'])) {
    json_error('Chunk no recibido');
}
$chunkPath = $tmpDir . DIRECTORY_SEPARATOR . ('part_' . $chunkIndex);
if (!@move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkPath)) {
    json_error('No se pudo almacenar el chunk');
}

// Si es el último chunk, ensamblar
$assembled = false;
$finalUrl = null;
if ($chunkIndex + 1 >= $totalChunks) {
    // Validar que existan todos los chunks
    for ($i = 0; $i < $totalChunks; $i++) {
        if (!file_exists($tmpDir . DIRECTORY_SEPARATOR . ('part_' . $i))) {
            json_error('Falta chunk ' . $i);
        }
    }

    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($fileName));
    $evasionDir = $baseUploads . DIRECTORY_SEPARATOR . 'evasion';
    if (!is_dir($evasionDir)) { @mkdir($evasionDir, 0777, true); }
    $finalPath = $evasionDir . DIRECTORY_SEPARATOR . date('Ymd_His') . '_' . $safeName;

    $out = @fopen($finalPath, 'wb');
    if (!$out) { json_error('No se pudo crear archivo final'); }
    for ($i = 0; $i < $totalChunks; $i++) {
        $part = $tmpDir . DIRECTORY_SEPARATOR . ('part_' . $i);
        $in = @fopen($part, 'rb');
        if ($in) {
            @stream_copy_to_stream($in, $out);
            @fclose($in);
        }
    }
    @fclose($out);

    // Limpiar temporal
    for ($i = 0; $i < $totalChunks; $i++) {
        @unlink($tmpDir . DIRECTORY_SEPARATOR . ('part_' . $i));
    }
    @rmdir($tmpDir);

    $assembled = true;
    $finalUrl = 'uploads/evasion/' . basename($finalPath);
}

echo json_encode([
    'success' => true,
    'assembled' => $assembled,
    'finalUrl' => $finalUrl,
]);
exit;
?>