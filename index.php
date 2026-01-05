<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video to Audio Converter</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .file-input {
            width: 100%;
            padding: 15px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            background: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-input:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: white;
            color: #333;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #eee;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
        }
        
        .result {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f0f8ff;
            border-radius: 10px;
            text-align: center;
        }
        
        .download-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: transform 0.3s;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
        }
        
        .error {
            color: #e74c3c;
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background: #ffeaea;
            border-radius: 5px;
            display: none;
        }
        
        .file-info {
            margin-top: 10px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎵 Video to Audio Converter</h1>
        <p class="subtitle">Convert your videos to high-quality audio files</p>
        
        <form id="converterForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="videoFile">Select Video File</label>
                <input type="file" id="videoFile" name="videoFile" accept=".mp4,.avi,.mov,.wmv,.flv,.mkv,.webm" class="file-input" required>
                <div class="file-info" id="fileInfo"></div>
            </div>
            
            <div class="form-group">
                <label for="outputFormat">Output Format</label>
                <select id="outputFormat" name="outputFormat">
                    <option value="mp3">MP3 (Recommended)</option>
                    <option value="wav">WAV (Lossless)</option>
                    <option value="ogg">OGG</option>
                    <option value="aac">AAC</option>
                    <option value="m4a">M4A</option>
                </select>
            </div>
            
            <button type="submit" class="btn" id="convertBtn">
                Convert to Audio
            </button>
        </form>
        
        <div class="progress-container" id="progressContainer">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <p id="progressText">Converting...</p>
        </div>
        
        <div class="error" id="errorMessage"></div>
        
        <div class="result" id="resultContainer">
            <h3>✅ Conversion Complete!</h3>
            <p>Your audio file is ready for download.</p>
            <a href="#" class="download-btn" id="downloadLink" download>Download Audio</a>
        </div>
    </div>

    <script>
        document.getElementById('videoFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.getElementById('fileInfo');
            
            if (file) {
                const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                fileInfo.textContent = `Selected: ${file.name} (${fileSize} MB)`;
            } else {
                fileInfo.textContent = '';
            }
        });
        
        document.getElementById('converterForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const convertBtn = document.getElementById('convertBtn');
            const progressContainer = document.getElementById('progressContainer');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const errorMessage = document.getElementById('errorMessage');
            const resultContainer = document.getElementById('resultContainer');
            const downloadLink = document.getElementById('downloadLink');
            
            // Reset UI
            errorMessage.style.display = 'none';
            resultContainer.style.display = 'none';
            progressContainer.style.display = 'block';
            progressFill.style.width = '0%';
            progressText.textContent = 'Uploading...';
            convertBtn.disabled = true;
            
            try {
                // Show progress (simulated)
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += Math.random() * 10;
                    if (progress > 90) progress = 90;
                    progressFill.style.width = progress + '%';
                }, 200);
                
                // Send request
                const response = await fetch('convert.php', {
                    method: 'POST',
                    body: formData
                });
                
                clearInterval(progressInterval);
                progressFill.style.width = '100%';
                progressText.textContent = 'Processing...';
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success
                    setTimeout(() => {
                        progressContainer.style.display = 'none';
                        resultContainer.style.display = 'block';
                        downloadLink.href = result.download_url;
                        downloadLink.textContent = `Download ${result.format.toUpperCase()} (${result.file_size})`;
                    }, 500);
                } else {
                    throw new Error(result.error || 'Conversion failed');
                }
                
            } catch (error) {
                progressContainer.style.display = 'none';
                errorMessage.textContent = error.message;
                errorMessage.style.display = 'block';
            } finally {
                convertBtn.disabled = false;
            }
        });
    </script>
</body>
</html>