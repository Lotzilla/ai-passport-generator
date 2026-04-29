<template>
  <!-- Full-screen overlay -->
  <div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4" @keydown.esc="cancel" tabindex="-1">
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col w-full max-w-lg">

      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <p class="font-semibold text-gray-900 text-sm">Take a Selfie</p>
        </div>
        <button @click="cancel" class="text-gray-400 hover:text-gray-700 transition-colors p-1 rounded-lg hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Camera / preview area -->
      <div class="relative bg-black" style="aspect-ratio: 4/3;">

        <!-- Camera error -->
        <div v-if="camError" class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-center px-6">
          <svg class="w-10 h-10 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
          </svg>
          <p class="text-white text-sm font-medium">Camera access denied</p>
          <p class="text-gray-400 text-xs">Please allow camera access in your browser settings, then try again.</p>
          <button @click="startCamera" class="mt-2 text-xs bg-white/10 hover:bg-white/20 text-white rounded-lg px-4 py-2 transition-colors">
            Try again
          </button>
        </div>

        <!-- Loading -->
        <div v-else-if="!streamReady || detectorLoading" class="absolute inset-0 flex flex-col items-center justify-center gap-3">
          <svg class="w-8 h-8 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
          </svg>
          <p class="text-gray-400 text-sm">{{ !streamReady ? 'Starting camera\u2026' : 'Initialising AI face detection\u2026' }}</p>
        </div>

        <!-- Live video -->
        <video
          v-show="streamReady && !detectorLoading && !snapshot"
          ref="videoRef"
          autoplay
          playsinline
          muted
          class="w-full h-full object-cover"
          style="transform: scaleX(-1);"
        ></video>

        <!-- Detection overlay canvas -->
        <canvas
          v-show="streamReady && !detectorLoading && !snapshot"
          ref="overlayRef"
          class="absolute inset-0 w-full h-full pointer-events-none"
          style="transform: scaleX(-1);"
        ></canvas>

        <!-- Glasses warning banner -->
        <div
          v-if="streamReady && !detectorLoading && !snapshot && glassesDetected"
          class="absolute top-3 inset-x-3 bg-amber-500/95 rounded-xl px-4 py-2.5 flex items-center gap-2.5 shadow-lg"
        >
          <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
          <p class="text-white text-xs font-semibold">Glasses detected — please remove them before taking your passport photo</p>
        </div>

        <!-- Status badge -->
        <div v-if="streamReady && !detectorLoading && !snapshot" class="absolute bottom-3 inset-x-0 flex justify-center pointer-events-none">
          <div
            class="text-white text-xs font-semibold rounded-full px-4 py-1.5 transition-all duration-300"
            :class="{
              'bg-green-500/90': faceFound && !glassesDetected,
              'bg-amber-500/90': faceFound && glassesDetected,
              'bg-black/50':     !faceFound,
            }"
          >
            <span v-if="glassesDetected">Remove glasses to continue</span>
            <span v-else-if="faceFound">Face detected &mdash; ready to capture</span>
            <span v-else>Position your face in the frame</span>
          </div>
        </div>

        <!-- Snapshot preview -->
        <canvas v-show="snapshot" ref="canvasRef" class="w-full h-full object-cover" style="transform: scaleX(-1);"></canvas>
        <div v-if="snapshot" class="absolute top-3 left-3">
          <span class="text-xs bg-green-500 text-white font-semibold rounded-full px-3 py-1">Photo taken &#10003;</span>
        </div>
      </div>

      <!-- Camera switcher -->
      <div v-if="cameras.length > 1 && !snapshot" class="px-5 pt-3 pb-1">
        <select
          v-model="selectedCamera"
          @change="switchCamera"
          class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
        >
          <option v-for="cam in cameras" :key="cam.deviceId" :value="cam.deviceId">
            {{ cam.label || `Camera ${cam.index + 1}` }}
          </option>
        </select>
      </div>

      <!-- Action buttons -->
      <div class="flex gap-3 px-5 py-4">
        <button
          v-if="snapshot"
          @click="retake"
          class="flex-1 flex items-center justify-center gap-2 border border-gray-200 hover:border-indigo-300 text-gray-600 hover:text-indigo-600 text-sm font-medium rounded-xl py-3 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Retake
        </button>

        <button
          v-if="!snapshot && streamReady && !detectorLoading"
          @click="capture"
          :disabled="!canCapture"
          class="flex-1 flex items-center justify-center gap-2 text-white text-sm font-semibold rounded-xl py-3 transition-all shadow-md"
          :class="canCapture ? 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-100' : 'bg-gray-400 cursor-not-allowed shadow-gray-100'"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span v-if="glassesDetected">Remove glasses first</span>
          <span v-else-if="faceFound">Take Photo</span>
          <span v-else>No face detected</span>
        </button>

        <button
          v-if="snapshot"
          @click="usePhoto"
          class="flex-1 flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl py-3 transition-colors shadow-md shadow-green-100"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          Use This Photo
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { FaceDetector, FilesetResolver } from '@mediapipe/tasks-vision'

const emit = defineEmits(['captured', 'cancel'])

// ── Refs ──────────────────────────────────────────────────────────────────────
const videoRef        = ref(null)
const canvasRef       = ref(null)
const overlayRef      = ref(null)
const streamReady     = ref(false)
const snapshot        = ref(false)
const camError        = ref(false)
const cameras         = ref([])
const selectedCamera  = ref(null)
const detectorLoading = ref(true)
const faceFound       = ref(false)
const glassesDetected = ref(false)

const canCapture = computed(() => faceFound.value && !glassesDetected.value)

// ── Internals ─────────────────────────────────────────────────────────────────
let activeStream     = null
let faceDetector     = null
let detectionRafId   = null
let lastDetectTime   = 0
let lastGlassesCheck = 0
let smoothBox        = null
let lastKeypoints    = null

// ── MediaPipe initialisation ──────────────────────────────────────────────────
async function initFaceDetector() {
  detectorLoading.value = true
  try {
    const vision = await FilesetResolver.forVisionTasks(
      'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/wasm'
    )
    faceDetector = await FaceDetector.createFromOptions(vision, {
      baseOptions: {
        modelAssetPath:
          'https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite',
        delegate: 'GPU',
      },
      runningMode: 'VIDEO',
      minDetectionConfidence: 0.5,
      minSuppressionThreshold: 0.3,
    })
  } catch (e) {
    console.warn('[CameraCapture] MediaPipe init failed, using fallback mode:', e)
    faceDetector     = null
    faceFound.value   = true
    glassesDetected.value = false
  }
  detectorLoading.value = false
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(async () => {
  await initFaceDetector()
  await enumerateCameras()
  await startCamera()
})

onBeforeUnmount(() => {
  stopDetectionLoop()
  stopStream()
  faceDetector?.close()
})

// ── Camera enumeration ────────────────────────────────────────────────────────
async function enumerateCameras() {
  try {
    const devices = await navigator.mediaDevices.enumerateDevices()
    cameras.value = devices
      .filter(d => d.kind === 'videoinput')
      .map((d, i) => ({ ...d, index: i }))
    const front = cameras.value.find(c => /front|selfie|user|facing front/i.test(c.label))
    selectedCamera.value = front?.deviceId ?? cameras.value[0]?.deviceId ?? null
  } catch { /* needs permission first */ }
}

// ── Start / stop stream ───────────────────────────────────────────────────────
async function startCamera() {
  camError.value        = false
  streamReady.value     = false
  snapshot.value        = false
  faceFound.value       = !faceDetector
  glassesDetected.value = false
  smoothBox             = null
  lastKeypoints         = null
  stopDetectionLoop()
  stopStream()

  const constraints = {
    video: {
      width:  { ideal: 1280 },
      height: { ideal: 720 },
      facingMode: selectedCamera.value ? undefined : 'user',
      ...(selectedCamera.value ? { deviceId: { exact: selectedCamera.value } } : {}),
    },
    audio: false,
  }

  try {
    activeStream = await navigator.mediaDevices.getUserMedia(constraints)
    videoRef.value.srcObject = activeStream
    await videoRef.value.play()
    streamReady.value = true
    await enumerateCameras()
    if (faceDetector) startDetectionLoop()
  } catch {
    camError.value = true
  }
}

function stopStream() {
  activeStream?.getTracks().forEach(t => t.stop())
  activeStream = null
}

async function switchCamera() { await startCamera() }

// ── Detection loop ────────────────────────────────────────────────────────────
function startDetectionLoop() {
  stopDetectionLoop()
  detectionRafId = requestAnimationFrame(detectionFrame)
}

function stopDetectionLoop() {
  if (detectionRafId) { cancelAnimationFrame(detectionRafId); detectionRafId = null }
}

function detectionFrame(timestamp) {
  if (!streamReady.value || snapshot.value) return

  if (faceDetector && timestamp - lastDetectTime > 100) {
    lastDetectTime = timestamp
    runDetection(timestamp)
  }

  drawOverlay()
  detectionRafId = requestAnimationFrame(detectionFrame)
}

function runDetection(timestamp) {
  const video = videoRef.value
  if (!video || video.readyState < 2 || !video.videoWidth) return

  try {
    const result = faceDetector.detectForVideo(video, timestamp)

    if (result.detections && result.detections.length > 0) {
      const det = result.detections[0]
      faceFound.value = true
      lastKeypoints   = det.keypoints

      const bb = det.boundingBox
      lerpToBox({ x: bb.originX, y: bb.originY, width: bb.width, height: bb.height })

      if (timestamp - lastGlassesCheck > 250) {
        lastGlassesCheck      = timestamp
        glassesDetected.value = checkGlasses(lastKeypoints)
      }
    } else {
      faceFound.value       = false
      glassesDetected.value = false
      lastKeypoints         = null
      smoothBox             = null
    }
  } catch { /* ignore transient errors */ }
}

// ── Glasses detection (pixel sampling in eye region) ─────────────────────────
function checkGlasses(keypoints) {
  if (!keypoints || keypoints.length < 2) return false

  const video = videoRef.value
  if (!video || !video.videoWidth) return false

  const vw = video.videoWidth
  const vh = video.videoHeight

  const ex0 = keypoints[0].x * vw
  const ex1 = keypoints[1].x * vw
  const ey0 = keypoints[0].y * vh
  const ey1 = keypoints[1].y * vh

  const ied   = Math.max(1, Math.abs(ex1 - ex0))
  const minEX = Math.min(ex0, ex1)
  const eyeCY = (ey0 + ey1) / 2

  const roiX = Math.max(0, minEX - ied * 0.52)
  const roiY = Math.max(0, eyeCY - ied * 0.40)
  const roiW = Math.min(vw - roiX, ied * 2.04)
  const roiH = Math.min(vh - roiY, ied * 0.70)

  if (roiW < 8 || roiH < 8) return false

  const tmp = document.createElement('canvas')
  tmp.width  = Math.round(roiW)
  tmp.height = Math.round(roiH)
  const ctx = tmp.getContext('2d', { willReadFrequently: true })
  ctx.drawImage(video, roiX, roiY, roiW, roiH, 0, 0, tmp.width, tmp.height)

  const { data: px } = ctx.getImageData(0, 0, tmp.width, tmp.height)

  let total = 0, dark = 0, bright = 0

  for (let i = 0; i < px.length; i += 4) {
    const lum = 0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2]
    total++
    if (lum < 72) dark++
    else if (lum > 218) bright++
  }

  if (!total) return false

  return (dark / total) > 0.12 || (bright / total) > 0.09
}

// ── Bounding box lerp ─────────────────────────────────────────────────────────
function lerpToBox(raw) {
  const t = 0.35
  if (!smoothBox) {
    smoothBox = { ...raw }
  } else {
    smoothBox.x      += (raw.x      - smoothBox.x)      * t
    smoothBox.y      += (raw.y      - smoothBox.y)      * t
    smoothBox.width  += (raw.width  - smoothBox.width)  * t
    smoothBox.height += (raw.height - smoothBox.height) * t
  }
}

// ── Overlay drawing ───────────────────────────────────────────────────────────
function drawOverlay() {
  const canvas = overlayRef.value
  const video  = videoRef.value
  if (!canvas || !video || !video.videoWidth) return

  const rect = video.getBoundingClientRect()
  const dw = Math.round(rect.width)
  const dh = Math.round(rect.height)
  if (canvas.width !== dw || canvas.height !== dh) {
    canvas.width  = dw
    canvas.height = dh
  }

  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, dw, dh)

  if (faceFound.value && smoothBox) {
    drawFaceBox(ctx, scaleToCanvas(smoothBox, video, dw, dh), glassesDetected.value)
  } else {
    drawGuideCorners(ctx, dw, dh)
  }
}

function scaleToCanvas(raw, video, cw, ch) {
  const scale = Math.max(cw / video.videoWidth, ch / video.videoHeight)
  return {
    x:      raw.x      * scale - (video.videoWidth  * scale - cw) / 2,
    y:      raw.y      * scale - (video.videoHeight * scale - ch) / 2,
    width:  raw.width  * scale,
    height: raw.height * scale,
  }
}

function drawFaceBox(ctx, box, isWarning) {
  const { x, y, width, height } = box
  const cs     = Math.min(width, height) * 0.22
  const lw     = Math.max(2, Math.min(4, width * 0.025))
  const colour = isWarning ? '#f59e0b' : '#22c55e'

  ctx.fillStyle = isWarning ? 'rgba(245,158,11,0.08)' : 'rgba(34,197,94,0.06)'
  ctx.fillRect(x, y, width, height)

  ctx.strokeStyle = colour
  ctx.lineWidth   = lw + 1
  ctx.lineCap     = 'round'
  ctx.lineJoin    = 'round'
  drawCorners(ctx, x, y, width, height, cs)

  ctx.strokeStyle = 'rgba(255,255,255,0.8)'
  ctx.lineWidth   = lw - 0.5
  drawCorners(ctx, x + 2, y + 2, width - 4, height - 4, cs - 2)
}

function drawGuideCorners(ctx, cw, ch) {
  const boxW = cw * 0.55
  const boxH = ch * 0.72
  const bx   = (cw - boxW) / 2
  const by   = (ch - boxH) / 2 - ch * 0.02
  ctx.strokeStyle = 'rgba(255,255,255,0.35)'
  ctx.lineWidth   = 2.5
  ctx.lineCap     = 'round'
  ctx.lineJoin    = 'round'
  drawCorners(ctx, bx, by, boxW, boxH, boxW * 0.14)
}

function drawCorners(ctx, x, y, w, h, cs) {
  ctx.beginPath()
  ctx.moveTo(x,         y + cs);      ctx.lineTo(x,     y);     ctx.lineTo(x + cs,     y)
  ctx.moveTo(x + w - cs, y);          ctx.lineTo(x + w, y);     ctx.lineTo(x + w,      y + cs)
  ctx.moveTo(x,         y + h - cs);  ctx.lineTo(x,     y + h); ctx.lineTo(x + cs,     y + h)
  ctx.moveTo(x + w - cs, y + h);      ctx.lineTo(x + w, y + h); ctx.lineTo(x + w,      y + h - cs)
  ctx.stroke()
}

// ── Capture ───────────────────────────────────────────────────────────────────
function capture() {
  if (!canCapture.value) return

  const video  = videoRef.value
  const canvas = canvasRef.value

  canvas.width  = video.videoWidth
  canvas.height = video.videoHeight

  const ctx = canvas.getContext('2d')
  ctx.translate(canvas.width, 0)
  ctx.scale(-1, 1)
  ctx.drawImage(video, 0, 0)

  stopDetectionLoop()
  stopStream()
  snapshot.value = true
}

function retake() {
  snapshot.value = false
  startCamera()
}

function usePhoto() {
  canvasRef.value.toBlob(blob => {
    emit('captured', new File([blob], 'selfie.jpg', { type: 'image/jpeg' }))
  }, 'image/jpeg', 0.92)
}

function cancel() {
  stopDetectionLoop()
  stopStream()
  faceDetector?.close()
  faceDetector = null
  emit('cancel')
}
</script>
