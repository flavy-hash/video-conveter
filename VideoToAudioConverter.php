<?php

class VideoToAudioConverter {
    private $ffmpegPath;
    private $allowedVideoTypes = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'];
    private $maxFileSize = 500 * 1024 * 1024; // 500MB
    private $uploadDir = 'uploads/';
    private $outputDir = 'converted/';
    private $logDir = 'logs/';
    
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
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }
    
    public function convert($videoFile, $outputFormat = 'mp3', $bitrate = '192k') {
        $uniqueId = uniqid();
        $logFileName = $uniqueId . '.log';
        $logFilePath = $this->logDir . $logFileName;

        try {
            // Validate input
            $this->validateFile($videoFile);
            
            // Generate unique filenames
            $uploadedFilePath = $this->uploadDir . $uniqueId . '_' . basename($videoFile['name']);
            $outputFileName = $uniqueId . '_converted.' . $outputFormat;
            $outputFilePath = $this->outputDir . $outputFileName;
            
            // Upload video file
            if (!move_uploaded_file($videoFile['tmp_name'], $uploadedFilePath)) {
                $this->writeLog($logFilePath, "Failed to move uploaded file: " . print_r($videoFile, true));
                throw new Exception("Failed to upload file.");
            }
            
            // Convert video to audio
            $command = $this->buildCommand($uploadedFilePath, $outputFilePath, $outputFormat, $bitrate);
            $this->executeConversion($command, $outputFilePath, $logFilePath);
            
            // Clean up uploaded video file
            unlink($uploadedFilePath);

            $this->writeLog($logFilePath, "Conversion successful: output={$outputFilePath}");
            
            return [
                'success' => true,
                'output_file' => $outputFileName,
                'file_path' => $outputFilePath,
                'download_url' => $this->outputDir . $outputFileName,
                'format' => $outputFormat,
                'log_file' => $logFileName
            ];
            
        } catch (Exception $e) {
            $this->writeLog($logFilePath, "Error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'CONVERSION_FAILED',
                'log_file' => $logFileName
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
    
    private function executeConversion($command, $outputFilePath, $logFilePath) {
        $output = [];
        $returnCode = 0;
        
        exec($command, $output, $returnCode);
        
        // Write log (command and output)
        $logContent = "COMMAND: " . $command . "\n\nOUTPUT:\n" . implode("\n", $output) . "\n\nRETURN_CODE: " . $returnCode . "\n";
        $this->writeLog($logFilePath, $logContent);
        
        if ($returnCode !== 0) {
            throw new Exception("Conversion failed. See log: " . $logFilePath);
        }
        
        if (!file_exists($outputFilePath)) {
            $this->writeLog($logFilePath, "Output file missing after conversion.\n");
            throw new Exception("Output file was not created. See log: " . $logFilePath);
        }
    }
    
    public function getSupportedFormats() {
        return ['mp3', 'wav', 'ogg', 'aac', 'm4a'];
    }

    private function writeLog($path, $content) {
        $time = date('c');
        file_put_contents($path, "[$time] " . $content . "\n", FILE_APPEND | LOCK_EX);
    }
    
    public function setMaxFileSize($sizeInMB) {
        $this->maxFileSize = $sizeInMB * 1024 * 1024;
    }
    
    public function setAllowedVideoTypes($types) {
        $this->allowedVideoTypes = $types;
    }
}