<?php
$file = $_GET['file'] ?? '';
$outputDir = 'converted/';

if (!empty($file) && file_exists($outputDir . $file)) {
    $filepath = $outputDir . $file;
    $filename = basename($filepath);
    
    header('Content-Type: audio/mpeg');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache');
    
    readfile($filepath);
    
    // Optional: Delete file after download
    // unlink($filepath);
    
    exit;
} else {
    header("HTTP/1.0 404 Not Found");
    echo "File not found.";
}
?>