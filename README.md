# PassportAI — Passport Photo Generator

A Laravel 11 + Vue 3 (Inertia.js) web application that converts a selfie into a
passport-compliant photo via a structured, modular processing pipeline.

---

## Tech Stack

| Layer    | Technology                                |
|----------|-------------------------------------------|
| Backend  | Laravel 11, PHP 8.2+                      |
| Frontend | Vue 3, Inertia.js, Tailwind CSS           |
| Build    | Vite                                      |
| Images   | Intervention Image v3 (GD driver)         |
| BG API   | remove.bg (optional, falls back to GD)    |

---

## Processing Pipeline

```
Upload → Background Removal → Face Detection → Smart Crop
       → Lighting Adjustment → Compliance Validation → Output
```

Each step is a dedicated service class in `app/Services/Pipeline/`.

---

## Quick Start

### Prerequisites
- PHP 8.2+, Composer
- Node 20+, npm
- GD extension enabled (`extension=gd` in php.ini)

### Install

```bash
git clone <repo>
cd AiPassportGenerator

composer install
npm install

cp .env.example .env
php artisan key:generate

# Create SQLite DB (used only for sessions/cache in SQLite mode)
touch database/database.sqlite

# Create required storage dirs
mkdir -p storage/app/uploads storage/app/processed storage/app/tmp

npm run build   # or: npm run dev
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000).

---

## Environment Variables

| Variable              | Description                                                         |
|-----------------------|---------------------------------------------------------------------|
| `REMOVE_BG_API_KEY`   | API key from [remove.bg](https://www.remove.bg/api). Optional.     |
| `FACE_DETECTION_API_URL` | URL of a face-detection micro-service. Optional (heuristic fallback). |

### remove.bg setup (recommended)
1. Sign up at https://www.remove.bg/api
2. Copy your API key
3. Set `REMOVE_BG_API_KEY=your_key` in `.env`

### Face Detection Micro-service (optional)
The app ships with a heuristic fallback that works for frontal selfies.
For production, run a Python service using `face_recognition` or MediaPipe:

```
POST /detect
Body: multipart/form-data  { image: <file> }
Response: { detected: true, x: 100, y: 80, width: 200, height: 260, eye_y: 170, img_w: 600, img_h: 800 }
```

Set `FACE_DETECTION_API_URL=http://localhost:5001/detect`.

---

## API Routes

| Method | URI                           | Description                         |
|--------|-------------------------------|-------------------------------------|
| GET    | `/`                           | SPA entry point                     |
| POST   | `/upload-photo`               | Upload selfie → returns `filename`  |
| POST   | `/process-photo`              | Run pipeline → returns result       |
| GET    | `/download-photo/{filename}`  | Download processed image            |

### POST `/upload-photo`
```json
// Request: multipart/form-data
{ "image": <file> }

// Response
{ "success": true, "filename": "uuid.jpg" }
```

### POST `/process-photo`
```json
// Request
{ "filename": "uuid.jpg", "country": "US" }

// Success response
{ "success": true, "filename": "passport-uuid.png", "url": "/download-photo/...", "message": "..." }

// Failure response
{ "success": false, "errors": ["Face too small", "Face not centred"] }
```

---

## Supported Countries

| Code | Country / Standard | Dimensions   |
|------|--------------------|--------------|
| US   | United States      | 2×2"         |
| GB   | United Kingdom     | 35×45mm      |
| EU   | EU / Schengen      | 35×45mm      |
| CA   | Canada             | 50×70mm      |
| AU   | Australia          | 35×45mm      |

Add new countries in `app/Services/PassportRulesService.php`.

---

## File Structure

```
app/
  Http/Controllers/PassportPhotoController.php
  Services/
    PassportRulesService.php          ← country rules registry
    Pipeline/
      BackgroundRemovalService.php    ← Step 2
      FaceDetectionService.php        ← Step 3
      ImageCropService.php            ← Step 4
      LightingAdjustmentService.php   ← Step 5
      ComplianceValidatorService.php  ← Step 6
      OutputService.php               ← Step 7

resources/js/
  Pages/Home.vue                      ← main page
  Components/
    ChatWindow.vue
    ImageUpload.vue
    ImagePreview.vue
  composables/
    usePassportStore.js               ← reactive state

routes/web.php
config/services.php
```

---

## Extending the Pipeline

- **New country rule**: add entry to `PassportRulesService::$rules`
- **New pipeline step**: create `app/Services/Pipeline/MyStepService.php`,
  inject it in `PassportPhotoController`, call it in `process()`
- **Replace heuristic face detection**: set `FACE_DETECTION_API_URL`

---

## License

MIT
