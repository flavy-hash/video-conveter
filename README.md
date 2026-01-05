# Video-to-Audio Converter

A small PHP service that converts uploaded video files to audio using FFmpeg. The project stores converted audio files in `converted/`, keeps temporary uploads in `uploads/`, and writes per-job FFmpeg logs to `logs/`.

---

## Quick overview

- Convert videos to: **mp3**, **wav**, **ogg**, **aac**, **m4a**
- Main files:
  - `convert.php` — POST endpoint for conversion (returns JSON)
  - `download.php` — GET endpoint to download converted audio (`?file=<filename>`)
  - `VideoToAudioConverter.php` — core conversion logic, validation, and logging
  - `uploads/` — temporary uploads
  - `converted/` — converted audio files
  - `logs/` — per-job log files (e.g., `5f2a3b4c123.log`)

---

## Requirements & setup

- PHP 7.2+ (or compatible)
- FFmpeg installed and in PATH (`ffmpeg` executable)
- Ensure writable directories for `uploads/`, `converted/`, `logs/` by the web server user

Basic steps:
1. Place project files on server
2. Install FFmpeg and ensure `ffmpeg` runs from the command line
3. Ensure the directories above are writable

> The app sets `ini_set('display_errors', 0)` to avoid PHP warnings corrupting JSON responses.

---

## API Endpoints

### POST /convert.php
Form-data:
- `videoFile` (file) — required
- `outputFormat` (string) — optional, default `mp3`

Success response:
```json
{
  "success": true,
  "output_file": "<id>_converted.mp3",
  "file_path": "converted/<id>_converted.mp3",
  "download_url": "converted/<id>_converted.mp3",
  "format": "mp3",
  "log_file": "<id>.log"
}
```

Failure response:
```json
{
  "success": false,
  "error": { "code": "CONVERSION_FAILED", "message": "Conversion failed. See log: logs/<id>.log" },
  "log_file": "<id>.log"
}
```

### GET /download.php?file=<filename>
Returns the audio file with correct headers for download.

---

## Logs — location & format

- Location: `logs/` directory
- Filename: `<job-id>.log`
- Contents:
  - `COMMAND:` — FFmpeg command executed
  - `OUTPUT:` — FFmpeg stdout/stderr
  - `RETURN_CODE:` — exit code
  - Timestamped entries are appended

Example:
```
[2026-01-05T12:34:56+00:00] COMMAND: ffmpeg -i "uploads/5f2a3b_in.mp4" -q:a 0 -map a "converted/5f2a3b_converted.mp3" 2>&1
[2026-01-05T12:34:56+00:00] OUTPUT:
ffmpeg version ...
Input #0, mov,mp4,m4a,3gp,3g2,mj2, from 'uploads/5f2a3b_in.mp4':
...
[2026-01-05T12:34:57+00:00] RETURN_CODE: 0
```

---

## Error codes

- `CONVERSION_FAILED` — FFmpeg returned non-zero exit code or output file not created; check `logs/<id>.log`.
- `SERVER_ERROR` — unexpected server-side exception; message included in response.

Note: validation errors (e.g., invalid file type, file too large) are currently reported as `CONVERSION_FAILED`; we can add finer-grained codes (`INVALID_FILE`, `FILE_TOO_LARGE`) if preferred.

---

## Security & recommendations

- Move `uploads/`, `converted/`, and `logs/` outside webroot or block direct access (e.g., `.htaccess`) to prevent information leakage and unauthorized downloads.
- Provide an authenticated endpoint to fetch logs if you need remote debugging.
- Limit allowed file types and size (the app enforces a 500 MB limit by default).

---

## Extending the project

- Add authenticated log retrieval or secure admin UI
- Add a queue worker for background conversions for large files
- Improve client progress reporting (FFmpeg progress parsing + websockets/polling)

---
