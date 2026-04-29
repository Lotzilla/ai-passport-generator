<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SupportChatController extends Controller
{
    /**
     * The full system prompt that tells the AI everything about PassportAI
     *  including every agent, every AI model used, and how the system works.
     */
    private string $systemPrompt = <<<'PROMPT'
You are a friendly, technically knowledgeable customer support assistant for **PassportAI**  a free web application that converts any selfie into an official passport photo using a fully automated 8-agent AI pipeline.

Your job is to help users understand the system, troubleshoot issues, and feel confident using the app. You know the entire technical stack in detail.

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
HOW THE APP WORKS  FULL PIPELINE
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
The user uploads any selfie and the system runs it through 8 specialised AI agents automatically. No user adjustments are needed  the system corrects everything.

**Agent 1  Upload Agent**
Validates the file (JPG/PNG, max 10 MB), stores it temporarily, and passes it to the pipeline.

**Agent 2  Face Detection Agent**
Uses a Python AI microservice (FastAPI + MediaPipe) to detect the face. MediaPipe runs two models in sequence:
  â€¢ FaceDetection  fast bounding-box detector (< 6 ms on CPU) using model_selection=1 (full-range, up to 5 metres)
  â€¢ FaceMesh  468 3D facial landmark predictor with iris refinement
The agent extracts: face bounding box (x, y, width, height), eye positions, nose tip, chin coordinates. Falls back to a PHP skin-tone heatmap detector if the Python service is unavailable.

**Agent 2.5  Deskew Agent** *(auto-straightening)*
Reads the eye coordinates from MediaPipe landmarks and computes the head roll angle using atan2(Î”y, Î”x). If the head is tilted between 1.5Â° and 30Â°, it rotates the entire image to make the eyes perfectly horizontal  exactly as passport standards require. All coordinates (faceBox + landmarks) are mathematically re-mapped to the new rotated image. Then re-detects the face on the straightened image for maximum accuracy.

**Agent 3  Background Removal Agent**
Uses a local Python AI microservice (FastAPI + rembg + UÂ²-Net) to remove the background and replace it with a plain white background. UÂ²-Net is a deep learning segmentation model (ONNX, ~170 MB weights) that achieves pixel-precise subject/background separation  including around hair, glasses, and complex edges. Priority chain:
  1. Local Python service (UÂ²-Net)  highest quality, runs on CPU
  2. remove.bg API  if API key is configured
  3. Hugging Face RMBG-1.4  if HF token is configured
  4. PHP GD multi-zone flood-fill  pure PHP fallback, no API needed

**Agent 4  Crop Agent**
Centres the face mathematically to match official passport standards for the selected country. Uses the eye-line (not the bounding-box top) as the vertical anchor  eyes are always placed at ~38â€“40% from the top of the photo. Face is horizontally centred exactly by computing `cropX = faceCenterX âˆ’ cropWidth/2`. Creates a white canvas so even off-centre originals always produce a perfectly centred result. Scales the result to the exact target pixel dimensions.

**Agent 5  Lighting Agent**
Normalises brightness using gamma correction (not linear brightness offset). Measures the average face luminance (skipping white background pixels), then solves for the gamma value that maps the face brightness to a target of 148/255. `imagegammacorrect()` is applied  this lifts dark shadows aggressively while preserving highlights and keeping the white background exactly 255. Also applies a contrast boost. Handles everything from dark indoor selfies to overexposed outdoor photos.

**Agent 6  Compliance Agent** *(advisory only)*
Logs an informational note if the face is significantly off-centre after crop (which would indicate a face detection accuracy issue, not a user problem). The photo is **always** saved and returned  compliance never blocks output.

**Agent 7  Response Agent**
Generates the final JSON response with the download URL and a user-friendly message.


THE PYTHON AI MICROSERVICE
A local FastAPI server (http://localhost:8000) runs alongside the Laravel backend.

Endpoints:
  GET  /health               health check
  POST /detect-face          MediaPipe face detection + landmarks
  POST /remove-background    UÂ²-Net background removal
  POST /process-image        combined detect + remove

AI models used:
  â€¢ MediaPipe FaceDetection + FaceMesh (Google)  face detection, 468-point landmark mesh
  â€¢ rembg + UÂ²-Net ONNX (Xuebinqin)  background segmentation, ~170 MB weights cached at ~/.u2net/
  â€¢ onnxruntime  ONNX model inference engine
  â€¢ OpenCV (opencv-python-headless)  image preprocessing

If the Python service is offline, the PHP agents fall back gracefully to their built-in methods (skin-tone heatmap + GD flood-fill). The passport photo is always generated regardless.

SUPPORTED COUNTRIES & OFFICIAL SPECS
â€¢ United States: 600Ã—600 px (2Ã—2" at 300dpi), face 70â€“80% of height, eyes at ~40% from top
â€¢ United Kingdom: 413Ã—531 px (35Ã—45mm at 300dpi), face 65â€“75% of height, eyes at ~38%
â€¢ EU / Schengen: 413Ã—531 px (35Ã—45mm at 300dpi), face 70â€“80% of height, eyes at ~38%
â€¢ Canada: 591Ã—827 px (50Ã—70mm at 300dpi), face 60â€“75% of height, eyes at ~38%
â€¢  Australia: 413Ã—531 px (35Ã—45mm at 300dpi), face 65â€“80% of height, eyes at ~38%

KEY DESIGN PRINCIPLE
The system is designed to handle ANY selfie and always produce a valid passport photo:
  âœ“ Dark or uneven lighting â†’ auto gamma correction
  âœ“ Head tilted left/right â†’ auto rotation via eye-landmark deskew
  âœ“ Busy or coloured background â†’ UÂ²-Net AI background removal
  âœ“ Face off-centre in original â†’ white-canvas crop centres it mathematically
  âœ“ Face too small/far away â†’ crop scales up to fill correct face-height %
  âœ“ Python AI service offline â†’ PHP fallbacks run automatically
The user never needs to re-take the photo.


PHOTO REQUIREMENTS (for best quality)

The system can handle most photos, but for the highest quality result:
  â€¢ Face the camera directly (the deskew agent corrects small tilts, but large yaw/pitch cannot be fixed by rotation)
  â€¢ Reasonable lighting (the lighting agent corrects dark/bright conditions but very harsh directional shadows can affect skin tone accuracy)
  â€¢ High resolution (at least 600Ã—600 px)
  â€¢ JPG or PNG format, max 10 MB


DOWNLOAD & OUTPUT
After processing, a "Download Passport Photo" button appears. The file is a PNG at the official pixel dimensions for the chosen country. Processing typically takes 20 seconds (most time is UÂ²-Net background removal on first run while ONNX weights download; ~5 seconds on subsequent runs).


PRICING & PRIVACY
PassportAI is completely free. No account or payment needed. Uploaded photos are processed in temporary storage and are not stored permanently.


TONE & BEHAVIOUR
  â€¢ Be warm, helpful, and concise.
  â€¢ For technical questions, give accurate technical answers  you know the full stack.
  â€¢ Keep replies to 3â€“5 sentences for simple questions. Expand for technical questions.
  â€¢ If someone is frustrated, empathise and give specific actionable advice.
  â€¢ Never make up features the app doesn't have.
  â€¢ If asked something unrelated to passport photos or this app, politely redirect.
  â€¢ Use plain English for non-technical users. Use technical terms when the user is clearly technical.
PROMPT;

    public function handle(Request $request)
    {
        $request->validate([
            'message'                => 'required|string|max:500',
            'history'                => 'nullable|array|max:20',
            'history.*.role'         => 'required|in:user,assistant',
            'history.*.content'      => 'required|string|max:1000',
            'context'                => 'nullable|array',
            'context.app_status'     => 'nullable|string|in:idle,processing,success,error',
            'context.country'        => 'nullable|string|size:2',
            'context.errors'         => 'nullable|array|max:10',
            'context.errors.*'       => 'nullable|string|max:300',
        ]);

        $message = trim($request->input('message'));
        $history = $request->input('history', []);
        $context = $request->input('context', []);

        // Use ChatGPT if key is configured
        if (config('services.openai.key')) {
            $reply = $this->askOpenAI($message, $history, $context);
            if ($reply) {
                return response()->json(['reply' => $reply]);
            }
        }

        // Fall back to rule-based FAQ matching (no API key set)
        return response()->json(['reply' => $this->matchFaq($message, $context)]);
    }

    private function askOpenAI(string $message, array $history, array $context = []): ?string
    {
        // Build message array: system prompt + live context + prior turns + new user message
        $systemContent = $this->systemPrompt . $this->buildContextBlock($context);
        $messages      = [['role' => 'system', 'content' => $systemContent]];

        foreach ($history as $turn) {
            $messages[] = [
                'role'    => $turn['role'],
                'content' => $turn['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $response = Http::withToken(config('services.openai.key'))
                ->timeout(15)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => 'gpt-4o-mini',
                    'max_tokens'  => 450,
                    'temperature' => 0.5,
                    'messages'    => $messages,
                ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json('choices.0.message.content') ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Build a dynamic context block injected at the end of the system prompt.
     * Gives the AI real-time awareness of where the user is in the flow.
     */
    private function buildContextBlock(array $context): string
    {
        if (empty($context)) {
            return '';
        }

        $status  = $context['app_status'] ?? 'idle';
        $country = strtoupper($context['country'] ?? 'US');
        $errors  = array_filter($context['errors'] ?? []);

        $countryNames = [
            'US' => 'United States (2x—2x", 600px—600px)',
            'GB' => 'United Kingdom (35mm—45mm, 413px—531px)',
            'EU' => 'EU / Schengen (35mm—45mm, 413px—531px)',
            'CA' => 'Canada (50mm—70mm, 591px—827px)',
            'AU' => 'Australia (35mm—45mm, 413px—531px)',
        ];
        $countryLabel = $countryNames[$country] ?? $country;

        $lines = [
            '',
            '=== LIVE USER SESSION CONTEXT ===',
            "Selected country: {$countryLabel}",
            "Current app status: {$status}",
        ];

        switch ($status) {
            case 'error':
                if (! empty($errors)) {
                    $lines[] = 'Pipeline returned an error. Details:';
                    foreach ($errors as $err) {
                        $lines[] = "  â€¢ {$err}";
                    }
                    $lines[] = 'INSTRUCTION: Note that the system is designed to always produce a photo  an "error" status means face detection failed entirely (no face found in the image). Address the specific error and explain what the user should check.';
                } else {
                    $lines[] = 'Processing failed  no face was detected in the photo.';
                    $lines[] = 'INSTRUCTION: The only hard failure is "no face detected". Give specific advice on photo angle and lighting to ensure the face is clearly visible.';
                }
                break;

            case 'success':
                $lines[] = 'Pipeline succeeded  the passport photo was generated and is ready to download.';
                $lines[] = 'INSTRUCTION: Congratulate the user. Guide them to download. Mention the photo meets official requirements for ' . $countryLabel . '.';
                break;

            case 'processing':
                $lines[] = 'The photo is currently being processed through the 8-agent AI pipeline.';
                $lines[] = 'INSTRUCTION: Reassure the user. Mention that UÂ²-Net background removal takes the most time (~5â€“20s). If asked, explain what each agent is doing.';
                break;

            default:
                $lines[] = 'The user has not yet uploaded a photo.';
                $lines[] = 'INSTRUCTION: Guide the user to select their country and upload any clear selfie. Emphasise the system handles lighting, background, and tilt automatically.';
                break;
        }

        $lines[] = '=== END CONTEXT ===';

        return "\n\n" . implode("\n", $lines);
    }

    /**
     * Rule-based fallback  used when no OpenAI API key is configured.
     */
    private function matchFaq(string $msg, array $context = []): string
    {
        $lower  = strtolower($msg);
        $status = $context['app_status'] ?? 'idle';
        $errors = array_filter($context['errors'] ?? []);

        // Context-aware: processing error
        if ($status === 'error' && preg_match('/why|what|fail|wrong|issue|problem|fix|help|error/i', $lower)) {
            if (! empty($errors)) {
                $errorList = implode("\nâ€¢ ", $errors);
                return "Here's what happened:\n {$errorList}\n\nThe most common cause is that no face was clearly detected. Make sure your face is well-lit and fully visible, then upload again.";
            }
            return "It looks like the system couldn't detect a face in your photo. Make sure you're facing the camera directly, the room is well-lit, and your full face is visible. Then try again!";
        }

        // Context-aware: success
        if ($status === 'success' && preg_match('/download|ready|done|next|print|use/i', $lower)) {
            return "Your photo is ready! Click the \"Download Passport Photo\" button to save it. The file meets official standards for your selected country and is ready for printing.";
        }

        $faq = [
            // Pipeline / AI tech questions
            'how.*work|how.*process|behind.*scene|ai.*work|8.*agent|eight.*agent|agent|pipeline' =>
                "Your photo goes through 8 AI agents automatically:\n1. Upload\n2. Face Detection (MediaPipe AI)\n3. Deskew (auto-straightens head tilt)\n4. Background Removal (UÂ²-Net AI)\n5. Crop & Centre\n6. Lighting Correction\n7. Compliance Check\n8. Response\nThe whole thing takes 20 seconds.",

            'mediapipe|face detect|landmark|bounding box' =>
                "Face detection uses Google's MediaPipe it runs two models: FaceDetection (finds the face bounding box in milliseconds) and FaceMesh (maps 468 3D facial landmarks including the exact eye positions). The eye positions are used to straighten the head and perfectly centre the face in the output.",

            'u2net|unet|rembg|background remov|segmentation|onnx' =>
                "Background removal uses UÂ²-Net” a deep learning model that runs locally via a Python FastAPI microservice. It achieves pixel-precise separation of the subject from the background, even around hair and glasses. The result is composited onto a plain white canvas. On first use it downloads ~170 MB of ONNX model weights.",

            'deskew|tilt|straighten|head.*lean|lean.*head|rotate|crooked' =>
                "The Deskew Agent automatically detects head tilt by measuring the angle between your eye positions (using MediaPipe landmarks). It then rotates the entire image to make the eyes perfectly horizontal  exactly as passport standards require. It handles tilts up to 30Â°.",

            'light|dark|shadow|bright|dim|exposure|gamma' =>
                "The Lighting Agent uses gamma correction to fix the brightness of your photo. It measures the average luminance of your face (ignoring the white background), then mathematically computes the correction needed to reach the ideal brightness level. This works far better than simple brightness adjustments for shadowy or dark selfies.",

            // Country questions
            'country|countries|which.*support|document.*type' =>
                "We support:\n United States (2x2\", 600px—600px)\nUnited Kingdom (35mm—45mm, 413px—531px)\n EU / Schengen (35mm—45mm, 413px—531px)\n Canada (50mm—70mm, 591px—827px)\n Australia (35mm—45mm, 413px—531px)",

            // File format
            'file|format|jpg|jpeg|png|size|mb|upload.*type' =>
                "We accept JPG and PNG images up to 10 MB. Higher resolution photos produce better results.",

            // Requirements
            'requir|photo.*tip|good.*photo|selfie.*tip|need.*photo|what.*upload' =>
                "The system auto-corrects most issues, but for the best quality: Face the camera directly (large yaw/pitch angles can't be fixed) Any lighting is fine  the system corrects it Any background is fine  UÂ²-Net removes it Max 10 MB, JPG or PNG",

            // Errors / failures
            'fail|wrong|error|not.*pass|reject|issue|problem|didn.*work|not.*work' =>
                "The only reason the pipeline can fail is if no face is detected at all. If that happened: Make sure your face is fully visible and well-lit Face the camera directly Don't cover your face with objects\n\nEverything else (background, lighting, tilt) is handled automatically  you don't need a perfect selfie!",

            // Time
            'how.*long|time|fast|slow|wait|second|minute' =>
                "Processing takes 20 seconds. Most of the time is background removal (UA²-Net AI model). The first run may be slower while the model weights download (~170 MB). Subsequent runs are much faster.",

            // Cost
            'price|cost|free|paid|payment|charge|money' =>
                "PassportAI is completely free to use! No account, no payment, no limits.",

            // Download
            'download|save|get.*photo|where.*photo|after.*done' =>
                "Once processing completes, a \"Download Passport Photo\" button appears. Click it to save a PNG at the official dimensions for your chosen country.",

            // Privacy
            'privacy|data|store|keep|gdpr|delete|personal' =>
                "Your photos are processed in temporary storage and are not stored permanently on our servers. We don't share your images with third parties.",

            // Greetings
            'hi|hello|hey|hiya|howdy|sup|yo' =>
                "Hey! I'm the PassportAI support assistant. I know the full technical pipeline ask me anything about how the app works, the AI models used, supported countries, or troubleshooting.",

            // Thanks 
            'thanks|thank you|thx|ty|cheers|great|awesome|perfect' =>
                "You're welcome! ðŸ˜Š Good luck with your passport photo!",
        ];

        foreach ($faq as $pattern => $answer) {
            if (preg_match('/' . $pattern . '/i', $lower)) {
                return $answer;
            }
        }

        return "I'm here to help! Ask me about: How the AI pipeline works (MediaPipe, UÂ²-Net, deskew) Supported countries and specs What photos work best Why a photo failed Download and printing";
    }
}
