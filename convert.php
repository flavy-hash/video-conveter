<?php
require_once 'VideoToAudioConverter.php';

// Prevent PHP warnings being sent as HTML which would break JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Check if FFmpeg is available
function isFFmpegAvailable() {
    $output = [];
    exec('ffmpeg -version 2>&1', $output, $returnCode);
    return $returnCode === 0;
}

// Handle the conversion
try {
    if (!isFFmpegAvailable()) {
        throw new Exception("FFmpeg is not installed on the server. Please contact administrator.");
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }
    
    if (!isset($_FILES['videoFile'])) {
        throw new Exception("No file uploaded.");
    }
    
    $outputFormat = $_POST['outputFormat'] ?? 'mp3';
    $converter = new VideoToAudioConverter();
    
    $result = $converter->convert($_FILES['videoFile'], $outputFormat);
    
    // Add file size information
    if ($result['success'] && file_exists($result['file_path'])) {
        $fileSize = filesize($result['file_path']);
        $result['file_size'] = formatFileSize($fileSize);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}
