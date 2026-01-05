<?php

class VideoToAudioConverter {
    private $ffmpegPath;
    private $allowedVideoTypes = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'];
    private $maxFileSize = 500 * 1024 * 1024; // 500MB
    private $uploadDir = 'uploads/';
    private $outputDir = 'converted/';
    
    public function __construct($ffmpegPath = 'ffmpeg') {
        $this->ffmpegPath = $ffmpegPath;
        $this->createDirectories();
    }
    
    private function createDirectories() {
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
    }
    
    public function convert($videoFile, $outputFormat = 'mp3', $bitrate = '192k') {
        try {
            // Validate input
            $this->validateFile($videoFile);
            
            // Generate unique filenames
            $uniqueId = uniqid();
            $uploadedFilePath = $this->uploadDir . $uniqueId . '_' . basename($videoFile['name']);
            $outputFileName = $uniqueId . '_converted.' . $outputFormat;
            $outputFilePath = $this->outputDir . $outputFileName;
            
            // Upload video file
            if (!move_uploaded_file($videoFile['tmp_name'], $uploadedFilePath)) {
                throw new Exception("Failed to upload file.");
            }
            
            // Convert video to audio
            $command = $this->buildCommand($uploadedFilePath, $outputFilePath, $outputFormat, $bitrate);
            $this->executeConversion($command, $outputFilePath);
            
            // Clean up uploaded video file
            unlink($uploadedFilePath);
            
            return [
                'success' => true,
                'output_file' => $outputFileName,
                'file_path' => $outputFilePath,
                'download_url' => $this->outputDir . $outputFileName,
                'format' => $outputFormat
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function validateFile($file) {
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception("Invalid file upload.");
        }
        
        // Check for upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception("No file uploaded.");
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception("File size exceeds limit.");
            default:
                throw new Exception("Upload error occurred.");
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception("File size exceeds maximum limit (500MB).");
        }
        
        // Check file type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $this->allowedVideoTypes)) {
            throw new Exception("Invalid file type. Allowed types: " . 
                              implode(', ', $this->allowedVideoTypes));
        }
    }
    
    private function buildCommand($inputPath, $outputPath, $format, $bitrate) {
        $commands = [
            'mp3' => "{$this->ffmpegPath} -i \"{$inputPath}\" -q:a 0 -map a \"{$outputPath}\" 2>&1",
            'wav' => "{$this->ffmpegPath} -i \"{$inputPath}\" -acodec pcm_s16le -ar 44100 \"{$outputPath}\" 2>&1",
            'ogg' => "{$this->ffmpegPath} -i \"{$inputPath}\" -acodec libvorbis -ab {$bitrate} \"{$outputPath}\" 2>&1",
            'aac' => "{$this->ffmpegPath} -i \"{$inputPath}\" -acodec aac -ab {$bitrate} \"{$outputPath}\" 2>&1",
            'm4a' => "{$this->ffmpegPath} -i \"{$inputPath}\" -acodec aac -ab {$bitrate} \"{$outputPath}\" 2>&1"
        ];
        
        if (isset($commands[$format])) {
            return $commands[$format];
        }
        
        // Default to mp3
        return $commands['mp3'];
    }
    
    private function executeConversion($command, $outputFilePath) {
        $output = [];
        $returnCode = 0;
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Conversion failed: " . implode("\n", $output));
        }
        
        if (!file_exists($outputFilePath)) {
            throw new Exception("Output file was not created.");
        }
    }
    
    public function getSupportedFormats() {
        return ['mp3', 'wav', 'ogg', 'aac', 'm4a'];
    }
    
    public function setMaxFileSize($sizeInMB) {
        $this->maxFileSize = $sizeInMB * 1024 * 1024;
    }
    
    public function setAllowedVideoTypes($types) {
        $this->allowedVideoTypes = $types;
    }
}