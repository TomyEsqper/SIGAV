<?php
require_once __DIR__ . '/../config/database.php';

function sigav_quote($arg) { return '"' . str_replace('"', '\\"', $arg) . '"'; }
function sigav_find_ffmpeg_local() {
    $local = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . (stripos(PHP_OS, 'WIN') === 0 ? 'ffmpeg.exe' : 'ffmpeg');
    if (file_exists($local)) return $local;
    $cands = [
        'C:\\ffmpeg\\bin\\ffmpeg.exe',
        'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
        'C:\\Program Files\\FFmpeg\\bin\\ffmpeg.exe',
        '/usr/bin/ffmpeg','/usr/local/bin/ffmpeg','/opt/homebrew/bin/ffmpeg'
    ];
    foreach ($cands as $c) { if (file_exists($c)) return $c; }
    return null;
}
function sigav_convert_to_mp4($inputPath, $outDir) {
    $ff = sigav_find_ffmpeg_local();
    if (!$ff || !file_exists($inputPath)) { return null; }
    @set_time_limit(120);
    $outfile = $outDir . DIRECTORY_SEPARATOR . pathinfo($inputPath, PATHINFO_FILENAME) . '_std.mp4';
    $cmd = sigav_quote($ff) . ' -y -i ' . sigav_quote($inputPath) . ' -loglevel error -map 0:v:0 -sn -c:v libx264 -preset veryfast -crf 23 -pix_fmt yuv420p -movflags +faststart ' . sigav_quote($outfile);
    @exec($cmd, $o, $code);
    if (file_exists($outfile) && @filesize($outfile) > 0) { return $outfile; }
    return null;
}

$db = getDB();
$rows = $db->fetchAll("SELECT id, archivo_url FROM evasion_detalle ORDER BY id ASC", []);
$base = dirname(__DIR__);
$ok = 0; $skip = 0; $fail = 0;
foreach ($rows as $r) {
    $url = $r['archivo_url'];
    if (!$url || stripos($url, '_std.mp4') !== false) { $skip++; continue; }
    $path = $base . DIRECTORY_SEPARATOR . $url;
    if (!is_file($path)) { $fail++; continue; }
    $dir = dirname($path);
    $out = sigav_convert_to_mp4($path, $dir);
    if ($out) {
        $newUrl = 'uploads/evasion/' . basename($out);
        $db->execute("UPDATE evasion_detalle SET archivo_url = ? WHERE id = ?", [$newUrl, (int)$r['id']]);
        echo "OK #{$r['id']}: " . basename($out) . "\n";
        $ok++;
    } else {
        echo "FALLÓ #{$r['id']}\n";
        $fail++;
    }
}

echo "\nResumen: OK=$ok, SKIP=$skip, FAIL=$fail\n";