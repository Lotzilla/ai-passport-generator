<template>
  <div class="min-h-screen bg-white">

    <!-- NAV -->
    <nav class="fixed top-0 inset-x-0 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center gap-6">
        <a href="#" class="flex items-center gap-2.5 flex-shrink-0">
          <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-sm">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/>
            </svg>
          </div>
          <span class="text-base font-bold text-gray-900">PassportAI</span>
        </a>
        <div class="hidden md:flex items-center gap-7 ml-4 text-sm text-gray-500 font-medium">
          <a href="#how-it-works" class="hover:text-gray-900 transition-colors">How it works</a>
          <a href="#features" class="hover:text-gray-900 transition-colors">Features</a>
          <a href="#pricing" class="hover:text-gray-900 transition-colors">Pricing</a>
          <a href="#faq" class="hover:text-gray-900 transition-colors">FAQ</a>
        </div>
        <div class="ml-auto flex items-center gap-3">
          <a href="#app" class="hidden sm:inline text-sm text-gray-500 hover:text-indigo-600 font-medium transition-colors">Sign in</a>
          <button @click="showToolModal = true" class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl px-4 py-2.5 transition-colors shadow-md shadow-indigo-100">
            Try for free
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
          </button>
        </div>
      </div>
    </nav>

    <!-- HERO -->
    <section class="pt-36 pb-24 px-6 bg-gradient-to-b from-indigo-50/70 via-white to-white">
      <div class="max-w-4xl mx-auto text-center">
        <div class="inline-flex items-center gap-2 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold rounded-full px-4 py-1.5 mb-8">
          <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
          AI-powered &middot; Officially compliant &middot; Free to start
        </div>
        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold text-gray-900 leading-tight tracking-tight">
          Passport photos in
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600"> 15 seconds</span>
        </h1>
        <p class="mt-7 text-xl text-gray-500 max-w-2xl mx-auto leading-relaxed">
          Upload a selfie. Our 7-agent AI pipeline removes the background, detects your face,
          checks official compliance, and delivers a print-ready photo for any country.
        </p>
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
          <button @click="showToolModal = true" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-base font-bold rounded-2xl px-8 py-4 transition-all shadow-2xl shadow-indigo-200 hover:scale-105">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
            </svg>
            Generate my passport photo
          </button>
          <a href="#how-it-works" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-900 text-base font-medium transition-colors">
            See how it works
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </a>
        </div>
        <p class="mt-6 text-xs text-gray-400">No account required &middot; Free plan available &middot; Accepted in 5 countries</p>
      </div>
    </section>

    <!-- STATS BAR -->
    <section class="border-y border-gray-100 bg-white py-10">
      <div class="max-w-5xl mx-auto px-6 grid grid-cols-2 sm:grid-cols-4 gap-8 text-center">
        <div>
          <p class="text-3xl font-extrabold text-gray-900">50K+</p>
          <p class="text-sm text-gray-400 mt-1">Photos processed</p>
        </div>
        <div>
          <p class="text-3xl font-extrabold text-gray-900">5</p>
          <p class="text-sm text-gray-400 mt-1">Countries supported</p>
        </div>
        <div>
          <p class="text-3xl font-extrabold text-gray-900">15s</p>
          <p class="text-sm text-gray-400 mt-1">Average processing time</p>
        </div>
        <div>
          <p class="text-3xl font-extrabold text-gray-900">98%</p>
          <p class="text-sm text-gray-400 mt-1">Compliance pass rate</p>
        </div>
      </div>
    </section>

    <!-- HOW IT WORKS -->
    <section id="how-it-works" class="py-28 px-6">
      <div class="max-w-5xl mx-auto">
        <div class="text-center mb-16">
          <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-3">How it works</p>
          <h2 class="text-4xl font-extrabold text-gray-900">Three steps to a perfect photo</h2>
          <p class="mt-4 text-gray-500 max-w-xl mx-auto">No editing skills. No photo booth trip. Upload and done.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="relative flex flex-col items-center text-center p-8 bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg hover:border-indigo-100 transition-all">
            <span class="absolute top-5 right-6 text-6xl font-black text-gray-50 select-none leading-none">1</span>
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center mb-5">
              <svg class="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Upload your selfie</h3>
            <p class="text-sm text-gray-500 leading-relaxed">Take a selfie with your phone or webcam, or upload any existing photo. JPG or PNG, up to 10 MB.</p>
          </div>
          <div class="relative flex flex-col items-center text-center p-8 bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg hover:border-violet-100 transition-all">
            <span class="absolute top-5 right-6 text-6xl font-black text-gray-50 select-none leading-none">2</span>
            <div class="w-14 h-14 rounded-2xl bg-violet-50 flex items-center justify-center mb-5">
              <svg class="w-7 h-7 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">AI processes your photo</h3>
            <p class="text-sm text-gray-500 leading-relaxed">7 AI agents remove the background, detect your face, crop to spec, adjust lighting, and validate compliance in ~15 seconds.</p>
          </div>
          <div class="relative flex flex-col items-center text-center p-8 bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg hover:border-green-100 transition-all">
            <span class="absolute top-5 right-6 text-6xl font-black text-gray-50 select-none leading-none">3</span>
            <div class="w-14 h-14 rounded-2xl bg-green-50 flex items-center justify-center mb-5">
              <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v4m0 0-3-3m3 3 3-3M12 4v8"/>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Download &amp; print</h3>
            <p class="text-sm text-gray-500 leading-relaxed">Download a print-ready PNG at the exact pixel dimensions required by your country's passport authority.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section id="features" class="py-28 px-6 bg-gray-50">
      <div class="max-w-5xl mx-auto">
        <div class="text-center mb-16">
          <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-3">Features</p>
          <h2 class="text-4xl font-extrabold text-gray-900">Built for accuracy, not just speed</h2>
          <p class="mt-4 text-gray-500 max-w-xl mx-auto">Every photo passes through 7 compliance stages — the same standards used by official photo centres.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <div v-for="feat in FEATURES" :key="feat.title" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" :class="feat.bg">
              <svg class="w-5 h-5" :class="feat.iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feat.icon"/>
              </svg>
            </div>
            <h3 class="font-bold text-gray-900 mb-1.5">{{ feat.title }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ feat.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- COUNTRIES -->
    <section class="py-28 px-6">
      <div class="max-w-5xl mx-auto">
        <div class="text-center mb-14">
          <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-3">Supported countries</p>
          <h2 class="text-4xl font-extrabold text-gray-900">Official specs. Zero guesswork.</h2>
          <p class="mt-4 text-gray-500 max-w-xl mx-auto">We bake each country's exact pixel dimensions and face-ratio rules directly into the pipeline.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="c in COUNTRIES" :key="c.code" class="flex items-center gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-indigo-200 hover:shadow-md transition-all cursor-default">
            <span class="text-3xl flex-shrink-0">{{ c.flag }}</span>
            <div class="flex-1 min-w-0">
              <p class="font-bold text-gray-900 text-sm truncate">{{ c.name }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ c.size }} &middot; {{ c.dims }}</p>
            </div>
            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-center text-sm text-gray-400 mt-6">More countries coming soon — <a href="#app" class="text-indigo-600 hover:underline font-medium">request yours</a></p>
      </div>
    </section>

    <!-- PRICING -->
    <section id="pricing" class="py-28 px-6 bg-gray-50">
      <div class="max-w-5xl mx-auto">
        <div class="text-center mb-16">
          <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-3">Pricing</p>
          <h2 class="text-4xl font-extrabold text-gray-900">Simple, honest pricing</h2>
          <p class="mt-4 text-gray-500">Start free. Upgrade when you need more.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
          <!-- Starter -->
          <div class="bg-white rounded-3xl border border-gray-200 p-8 flex flex-col">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Starter</p>
            <div class="mt-4 mb-2 flex items-end gap-2">
              <span class="text-4xl font-extrabold text-gray-900">Free</span>
            </div>
            <p class="text-sm text-gray-400 mb-8">No credit card needed</p>
            <ul class="space-y-3 flex-1 text-sm">
              <li v-for="item in PLANS.starter" :key="item.text" class="flex items-center gap-2.5" :class="item.included ? 'text-gray-700' : 'text-gray-300'">
                <svg v-if="item.included" class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <svg v-else class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ item.text }}
              </li>
            </ul>
            <a href="#app" class="mt-8 block text-center border border-gray-200 hover:border-indigo-300 text-gray-700 hover:text-indigo-700 font-semibold rounded-2xl py-3.5 text-sm transition-colors">Get started free</a>
          </div>
          <!-- Pro -->
          <div class="bg-indigo-600 rounded-3xl p-8 flex flex-col relative overflow-hidden shadow-2xl shadow-indigo-200 md:-mt-4 md:-mb-4">
            <div class="absolute top-0 right-0 bg-amber-400 text-amber-900 text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-bl-xl">Most popular</div>
            <p class="text-xs font-bold text-indigo-300 uppercase tracking-wider">Pro</p>
            <div class="mt-4 mb-2 flex items-end gap-2">
              <span class="text-4xl font-extrabold text-white">$4.99</span>
              <span class="text-indigo-300 text-sm mb-1">/ month</span>
            </div>
            <p class="text-sm text-indigo-300 mb-8">Billed monthly, cancel anytime</p>
            <ul class="space-y-3 flex-1 text-sm">
              <li v-for="item in PLANS.pro" :key="item.text" class="flex items-center gap-2.5" :class="item.included ? 'text-indigo-100' : 'text-indigo-400/50'">
                <svg v-if="item.included" class="w-4 h-4 text-indigo-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <svg v-else class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ item.text }}
              </li>
            </ul>
            <a href="#app" class="mt-8 block text-center bg-white hover:bg-indigo-50 text-indigo-700 font-bold rounded-2xl py-3.5 text-sm transition-colors shadow-lg">Start Pro — $4.99/mo</a>
          </div>
          <!-- Business -->
          <div class="bg-white rounded-3xl border border-gray-200 p-8 flex flex-col">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Business</p>
            <div class="mt-4 mb-2 flex items-end gap-2">
              <span class="text-4xl font-extrabold text-gray-900">$19.99</span>
              <span class="text-gray-400 text-sm mb-1">/ month</span>
            </div>
            <p class="text-sm text-gray-400 mb-8">For teams and developers</p>
            <ul class="space-y-3 flex-1 text-sm text-gray-700">
              <li v-for="item in PLANS.business" :key="item.text" class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                {{ item.text }}
              </li>
            </ul>
            <a href="#app" class="mt-8 block text-center border border-gray-200 hover:border-indigo-300 text-gray-700 hover:text-indigo-700 font-semibold rounded-2xl py-3.5 text-sm transition-colors">Contact sales</a>
          </div>
        </div>
      </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="py-28 px-6">
      <div class="max-w-5xl mx-auto">
        <div class="text-center mb-14">
          <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-3">What people say</p>
          <h2 class="text-4xl font-extrabold text-gray-900">Used by travellers worldwide</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <div v-for="t in TESTIMONIALS" :key="t.name" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col gap-4">
            <div class="flex gap-0.5">
              <svg v-for="n in 5" :key="n" class="w-4 h-4 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
            <p class="text-sm text-gray-600 leading-relaxed flex-1">"{{ t.quote }}"</p>
            <div class="flex items-center gap-3 pt-2 border-t border-gray-50">
              <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white" :class="t.avatarBg">{{ t.name[0] }}</div>
              <div>
                <p class="text-sm font-semibold text-gray-800">{{ t.name }}</p>
                <p class="text-xs text-gray-400">{{ t.role }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-28 px-6 bg-gray-50">
      <div class="max-w-3xl mx-auto">
        <div class="text-center mb-14">
          <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-3">FAQ</p>
          <h2 class="text-4xl font-extrabold text-gray-900">Common questions</h2>
        </div>
        <div class="space-y-3">
          <div v-for="(item, i) in FAQS" :key="i" class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
            <button @click="toggleFaq(i)" class="w-full flex items-center justify-between px-6 py-5 text-left gap-4 hover:bg-gray-50/50 transition-colors">
              <span class="font-semibold text-gray-900 text-sm leading-relaxed">{{ item.q }}</span>
              <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="{ 'rotate-180': openFaq === i }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div v-show="openFaq === i" class="px-6 pb-5 text-sm text-gray-500 leading-relaxed border-t border-gray-50 pt-3">
              {{ item.a }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FINAL CTA -->
    <section class="py-28 px-6 bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-700 relative overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-white rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-white rounded-full blur-3xl"></div>
      </div>
      <div class="relative max-w-3xl mx-auto text-center">
        <h2 class="text-4xl sm:text-5xl font-extrabold text-white leading-tight">Ready for your passport photo?</h2>
        <p class="mt-5 text-indigo-200 text-lg">It takes 15 seconds. No account required to start.</p>
        <button @click="showToolModal = true" class="mt-10 inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-indigo-700 font-bold text-base rounded-2xl px-8 py-4 transition-all shadow-2xl hover:scale-105">
          Generate my photo now
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
        </button>
      </div>
    </section>

    <!-- THE TOOL -->
    <section id="app" class="py-20 px-4 bg-gray-50">
      <div class="max-w-3xl mx-auto flex flex-col gap-6">

        <div class="text-center">
          <h2 class="text-3xl font-bold text-gray-900">Generate Your Passport Photo</h2>
          <p class="text-sm text-gray-500 mt-2">Upload a selfie — we'll crop, enhance, and format it to official passport standards in seconds.</p>
        </div>

        <!-- Step 1 — Country selector -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
            Step 1 — Choose country / document type
          </label>
          <select
            v-model="country"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white transition-colors"
            :disabled="status === 'processing'"
          >
            <option value="US">🇺🇸 United States (2x2")</option>
            <option value="GB">🇬🇧 United Kingdom (35x45mm)</option>
            <option value="EU">🇪🇺 EU / Schengen (35x45mm)</option>
            <option value="CA">🇨🇦 Canada (50x70mm)</option>
            <option value="AU">🇦🇺 Australia (35x45mm)</option>
          </select>
        </div>

        <!-- Step 2 — Photo input -->
        <div v-if="status === 'idle' || status === 'error'" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex flex-col gap-5">
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">
            Step 2 — Provide your photo
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Upload file -->
            <div
              class="flex flex-col items-center gap-4 border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center transition-colors cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30"
              :class="{ 'border-indigo-300 bg-indigo-50/40': dragging }"
              @dragover.prevent="dragging = true"
              @dragleave.prevent="dragging = false"
              @drop.prevent="onDrop"
            >
              <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center">
                <svg class="w-7 h-7 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-700">Upload a Photo</p>
                <p class="text-xs text-gray-400 mt-1">Drag &amp; drop or browse<br>JPG / PNG &middot; Max 10 MB</p>
              </div>
              <label class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl px-5 py-2.5 cursor-pointer transition-colors shadow-md shadow-indigo-100 select-none">
                {{ status === 'error' ? 'Try Again' : 'Choose File' }}
                <input type="file" accept="image/jpeg,image/png" class="hidden" @change="onFileInputChange" />
              </label>
            </div>
            <!-- Take selfie -->
            <div class="flex flex-col items-center gap-4 border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center transition-colors hover:border-violet-300 hover:bg-violet-50/30">
              <div class="w-14 h-14 rounded-2xl bg-violet-50 flex items-center justify-center">
                <svg class="w-7 h-7 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-700">Take a Selfie</p>
                <p class="text-xs text-gray-400 mt-1">Use your webcam or<br>phone camera live</p>
              </div>
              <button @click="showCamera = true" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-xl px-5 py-2.5 transition-colors shadow-md shadow-violet-100">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Open Camera
              </button>
            </div>
          </div>
          <div v-if="status === 'error'" class="rounded-xl bg-red-50 border border-red-200 p-4 flex gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="text-sm">
              <p class="font-semibold text-red-700" v-if="glassesError">Glasses are not allowed in passport photos.</p>
              <p class="font-semibold text-red-700" v-else-if="hatError">Hats and head coverings are not allowed in passport photos.</p>
              <p class="font-semibold text-red-700" v-else-if="noFaceError">No face detected in your photo.</p>
              <p class="font-semibold text-red-700" v-else>Something went wrong.</p>
              <ul v-if="glassesError" class="mt-1.5 space-y-1 text-red-600 text-xs list-disc list-inside">
                <li>Remove all eyeglasses, sunglasses, or tinted glasses</li>
                <li>Official passport standards (ICAO) do not permit glasses</li>
                <li>Retake the photo without any eyewear</li>
              </ul>
              <ul v-else-if="hatError" class="mt-1.5 space-y-1 text-red-600 text-xs list-disc list-inside">
                <li>Remove all hats, caps, beanies, or head coverings</li>
                <li>Your full head and face must be clearly visible</li>
                <li>Retake the photo without any headwear</li>
              </ul>
              <ul v-else-if="noFaceError" class="mt-1.5 space-y-1 text-red-600 text-xs list-disc list-inside">
                <li>Make sure your full face is clearly visible and not cropped</li>
                <li>Face the camera directly — avoid extreme angles</li>
                <li>Ensure the room is well-lit with no harsh shadows</li>
                <li>Remove sunglasses, masks, or anything covering your face</li>
              </ul>
              <p v-else class="text-red-600 text-xs mt-1">Try a clearer photo or ask our AI assistant for help.</p>
            </div>
          </div>
        </div>

        <!-- Camera capture overlay -->
        <CameraCapture v-if="showCamera" @captured="onCameraCaptured" @cancel="showCamera = false" />

        <!-- Processing — pipeline tracker -->
        <div v-if="status === 'processing'" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-5">Processing your photo…</label>
          <div class="space-y-4">
            <div v-for="step in STEPS" :key="step.id" class="flex items-center gap-4">
              <span v-if="step.id < currentStep" class="flex-shrink-0 w-7 h-7 rounded-full bg-green-500 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
              </span>
              <span v-else-if="step.id === currentStep" class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
              </span>
              <span v-else class="flex-shrink-0 w-7 h-7 rounded-full bg-gray-100 border border-gray-200 text-xs text-gray-400 flex items-center justify-center font-semibold">{{ step.id }}</span>
              <span class="text-sm" :class="step.id <= currentStep ? 'text-gray-800 font-medium' : 'text-gray-400'">{{ step.label }}</span>
            </div>
          </div>
        </div>

        <!-- Result — Before / After + Download -->
        <div v-if="status === 'success'" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-5">Step 3 — Your passport photo is ready!</label>
          <div class="grid grid-cols-2 gap-5 mb-6">
            <div class="flex flex-col gap-2">
              <p class="text-xs font-medium text-gray-400 text-center uppercase tracking-wide">Original</p>
              <div class="rounded-xl overflow-hidden border border-gray-100 aspect-[3/4] bg-gray-50">
                <img :src="originalPreview" class="w-full h-full object-cover" alt="Original photo" />
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <p class="text-xs font-semibold text-indigo-500 text-center uppercase tracking-wide">Passport Photo</p>
              <div class="rounded-xl overflow-hidden border-2 border-indigo-300 aspect-[3/4] bg-gray-50">
                <img :src="processedPreview" class="w-full h-full object-cover" alt="Passport photo" />
              </div>
            </div>
          </div>
          <a :href="downloadUrl" target="_blank" class="flex items-center justify-center gap-2 w-full bg-green-600 hover:bg-green-700 text-white font-bold text-sm rounded-xl py-4 transition-colors shadow-lg shadow-green-100">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
            Download Passport Photo
          </a>
          <button @click="startOver" class="mt-3 w-full text-sm text-gray-400 hover:text-indigo-600 border border-gray-200 hover:border-indigo-200 rounded-xl py-3 transition-colors">
            Process another photo
          </button>
        </div>

      </div>
    </section>

    <!-- FOOTER -->
    <footer class="border-t border-gray-100 bg-white py-14 px-6">
      <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-start gap-12">
        <div class="flex-1">
          <div class="flex items-center gap-2.5 mb-4">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-sm">
              <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/>
              </svg>
            </div>
            <span class="font-bold text-gray-900">PassportAI</span>
          </div>
          <p class="text-sm text-gray-400 max-w-xs leading-relaxed">AI-powered passport photo generator. Officially compliant for US, UK, EU, Canada, and Australia.</p>
        </div>
        <div class="grid grid-cols-2 gap-12 text-sm">
          <div>
            <p class="font-semibold text-gray-700 mb-4">Product</p>
            <ul class="space-y-2.5 text-gray-400">
              <li><a href="#how-it-works" class="hover:text-gray-700 transition-colors">How it works</a></li>
              <li><a href="#features" class="hover:text-gray-700 transition-colors">Features</a></li>
              <li><a href="#pricing" class="hover:text-gray-700 transition-colors">Pricing</a></li>
              <li><a href="#app" class="hover:text-gray-700 transition-colors">Try it free</a></li>
            </ul>
          </div>
          <div>
            <p class="font-semibold text-gray-700 mb-4">Legal</p>
            <ul class="space-y-2.5 text-gray-400">
              <li><a href="#" class="hover:text-gray-700 transition-colors">Privacy Policy</a></li>
              <li><a href="#" class="hover:text-gray-700 transition-colors">Terms of Service</a></li>
              <li><a href="#" class="hover:text-gray-700 transition-colors">Contact</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="max-w-5xl mx-auto mt-10 pt-6 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-400">
        <p>© 2026 PassportAI. All rights reserved.</p>
        <p>Photos processed temporarily. Not stored permanently on our servers.</p>
      </div>
    </footer>

    <!-- Floating support chat -->
    <SupportChat :app-status="status" :country="country" :pipeline-errors="pipelineErrors" />

    <!-- TOOL MODAL -->
    <Transition name="modal">
      <div v-if="showToolModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
<div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeToolModal"></div>

        <!-- Panel -->
        <div class="modal-panel relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto flex flex-col">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-base font-bold text-gray-900">Passport Photo Generator</p>
                <p class="text-xs text-gray-400">AI-powered · ~15 seconds</p>
              </div>
            </div>
            <button v-if="status !== 'processing'" @click="showToolModal = false" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors" aria-label="Close">
              <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 flex flex-col gap-5">

            <!-- Country selector -->
            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Country / document type</label>
              <select v-model="country" class="w-full rounded-xl border border-gray-200 bg-gray-50 text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white transition-colors" :disabled="status === 'processing'">
                <option value="US">🇺🇸 United States (2x2")</option>
                <option value="GB">🇬🇧 United Kingdom (35x45mm)</option>
                <option value="EU">🇪🇺 EU / Schengen (35x45mm)</option>
                <option value="CA">🇨🇦 Canada (50x70mm)</option>
                <option value="AU">🇦🇺 Australia (35x45mm)</option>
              </select>
            </div>

            <!-- Upload / Camera (idle or error) -->
            <div v-if="status === 'idle' || status === 'error'" class="flex flex-col gap-4">
              <div class="grid grid-cols-2 gap-4">
                <!-- Upload file -->
                <div
                  class="flex flex-col items-center gap-3 border-2 border-dashed border-gray-200 rounded-2xl p-5 text-center cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30 transition-colors"
                  :class="{ 'border-indigo-300 bg-indigo-50/40': dragging }"
                  @dragover.prevent="dragging = true"
                  @dragleave.prevent="dragging = false"
                  @drop.prevent="onDrop"
                >
                  <div class="w-11 h-11 rounded-xl bg-indigo-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-700">Upload Photo</p>
                    <p class="text-xs text-gray-400 mt-0.5">JPG / PNG · Max 10 MB</p>
                  </div>
                  <label class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl px-4 py-2 cursor-pointer transition-colors shadow-sm select-none">
                    {{ status === 'error' ? 'Try Again' : 'Choose File' }}
                    <input type="file" accept="image/jpeg,image/png" class="hidden" @change="onFileInputChange" />
                  </label>
                </div>

                <!-- Take selfie -->
                <div class="flex flex-col items-center gap-3 border-2 border-dashed border-gray-200 rounded-2xl p-5 text-center hover:border-violet-300 hover:bg-violet-50/30 transition-colors">
                  <div class="w-11 h-11 rounded-xl bg-violet-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-700">Take a Selfie</p>
                    <p class="text-xs text-gray-400 mt-0.5">Use your webcam live</p>
                  </div>
                  <button @click="showCamera = true" class="inline-flex items-center gap-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-xl px-4 py-2 transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Open Camera
                  </button>
                </div>
              </div>
              <div v-if="status === 'error'" class="rounded-xl bg-red-50 border border-red-200 p-4 flex gap-3">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-sm">
                  <p class="font-semibold text-red-700" v-if="glassesError">Glasses are not allowed in passport photos.</p>
                  <p class="font-semibold text-red-700" v-else-if="hatError">Hats and head coverings are not allowed in passport photos.</p>
                  <p class="font-semibold text-red-700" v-else-if="noFaceError">No face detected in your photo.</p>
                  <p class="font-semibold text-red-700" v-else>Something went wrong.</p>
                  <ul v-if="glassesError" class="mt-1.5 space-y-1 text-red-600 text-xs list-disc list-inside">
                    <li>Remove all eyeglasses, sunglasses, or tinted glasses</li>
                    <li>Official passport standards (ICAO) do not permit glasses</li>
                    <li>Retake the photo without any eyewear</li>
                  </ul>
                  <ul v-else-if="hatError" class="mt-1.5 space-y-1 text-red-600 text-xs list-disc list-inside">
                    <li>Remove all hats, caps, beanies, or head coverings</li>
                    <li>Your full head and face must be clearly visible</li>
                    <li>Retake the photo without any headwear</li>
                  </ul>
                  <ul v-else-if="noFaceError" class="mt-1.5 space-y-1 text-red-600 text-xs list-disc list-inside">
                    <li>Make sure your full face is clearly visible and not cropped</li>
                    <li>Face the camera directly — avoid extreme angles</li>
                    <li>Ensure the room is well-lit with no harsh shadows</li>
                    <li>Remove sunglasses, masks, or anything covering your face</li>
                  </ul>
                  <p v-else class="text-red-600 text-xs mt-1">Try a clearer photo or ask our AI assistant for help.</p>
                </div>
              </div>
            </div>

            <!-- Camera capture -->
            <CameraCapture v-if="showCamera" @captured="onCameraCaptured" @cancel="showCamera = false" />

            <!-- Processing — pipeline tracker -->
            <div v-if="status === 'processing'" class="flex flex-col gap-4">
              <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Processing your photo…</p>
              <div class="space-y-3">
                <div v-for="step in STEPS" :key="step.id" class="flex items-center gap-3">
                  <span v-if="step.id < currentStep" class="flex-shrink-0 w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                  </span>
                  <span v-else-if="step.id === currentStep" class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center">
                    <svg class="w-3 h-3 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                  </span>
                  <span v-else class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 border border-gray-200 text-xs text-gray-400 flex items-center justify-center font-semibold">{{ step.id }}</span>
                  <span class="text-sm" :class="step.id <= currentStep ? 'text-gray-800 font-medium' : 'text-gray-400'">{{ step.label }}</span>
                </div>
              </div>
            </div>

            <!-- Result —  before / after + download -->
            <div v-if="status === 'success'" class="flex flex-col gap-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                  <p class="text-xs font-medium text-gray-400 text-center uppercase tracking-wide">Original</p>
                  <div class="rounded-xl overflow-hidden border border-gray-100 aspect-[3/4] bg-gray-50">
                    <img :src="originalPreview" class="w-full h-full object-cover" alt="Original" />
                  </div>
                </div>
                <div class="flex flex-col gap-1.5">
                  <p class="text-xs font-semibold text-indigo-500 text-center uppercase tracking-wide">Passport Photo</p>
                  <div class="rounded-xl overflow-hidden border-2 border-indigo-300 aspect-[3/4] bg-gray-50">
                    <img :src="processedPreview" class="w-full h-full object-cover" alt="Result" />
                  </div>
                </div>
              </div>
              <a :href="downloadUrl" target="_blank" class="flex items-center justify-center gap-2 w-full bg-green-600 hover:bg-green-700 text-white font-bold text-sm rounded-xl py-3.5 transition-colors shadow-lg shadow-green-100">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
                Download Passport Photo
              </a>
              <button @click="startOver" class="text-sm text-gray-400 hover:text-indigo-600 border border-gray-200 hover:border-indigo-200 rounded-xl py-2.5 transition-colors">
                Process another photo
              </button>
            </div>

          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import SupportChat   from '../Components/SupportChat.vue'
import CameraCapture from '../Components/CameraCapture.vue'

// ── App state ──────────────────────────────────────────────────────────────
const showToolModal    = ref(false)
const status           = ref('idle')
const country          = ref('US')
const dragging         = ref(false)
const showCamera       = ref(false)
const originalPreview  = ref(null)
const processedPreview = ref(null)
const downloadUrl      = ref(null)
const currentStep      = ref(0)
const pipelineErrors   = ref([])

const noFaceError = computed(() =>
  pipelineErrors.value.some(e => /no face|face.*detect|detect.*face/i.test(e))
)
const glassesError = computed(() =>
  pipelineErrors.value.some(e => /glasses/i.test(e))
)
const hatError = computed(() =>
  pipelineErrors.value.some(e => /hat|head covering|headwear/i.test(e))
)

// ── Landing-page state ─────────────────────────────────────────────────────
const openFaq = ref(null)
function toggleFaq(i) { openFaq.value = openFaq.value === i ? null : i }

// ── Static data ────────────────────────────────────────────────────────────
const STEPS = [
  { id: 1, label: 'Upload Agent' },
  { id: 2, label: 'Face Detection' },
  { id: 3, label: 'Background Removal' },
  { id: 4, label: 'Smart Crop' },
  { id: 5, label: 'Lighting Adjustment' },
  { id: 6, label: 'Compliance Check' },
  { id: 7, label: 'Response Agent' },
]

const COUNTRIES = [
  { code: 'US', flag: '🇺🇸', name: 'United States',  size: '2x2"',    dims: '600x600 px' },
  { code: 'GB', flag: '🇬🇧', name: 'United Kingdom', size: '35x45mm', dims: '413x531 px' },
  { code: 'EU', flag: '🇪🇺', name: 'EU / Schengen',  size: '35x45mm', dims: '413x531 px' },
  { code: 'CA', flag: '🇨🇦', name: 'Canada',         size: '50x70mm', dims: '591x827 px' },
  { code: 'AU', flag: '🇦🇺', name: 'Australia',      size: '35x45mm', dims: '413x531 px' },
]

const FEATURES = [
  { title: 'AI Background Removal',   bg: 'bg-indigo-50', iconColor: 'text-indigo-600', icon: 'M5.25 7.5A2.25 2.25 0 017.5 5.25h9a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25h-9a2.25 2.25 0 01-2.25-2.25v-9z',       desc: 'Removes any background and replaces it with a clean white — exactly as required by passport authorities.' },
  { title: 'Smart Face Detection',    bg: 'bg-violet-50', iconColor: 'text-violet-600', icon: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z', desc: 'Precisely locates your face, centres it, and verifies it meets the required face-to-photo height ratio for each country.' },
  { title: 'Compliance Validation',   bg: 'bg-green-50',  iconColor: 'text-green-600',  icon: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',                                                                desc: 'Measures face size %, horizontal centring, and background brightness against official government specifications.' },
  { title: 'Lighting Normalisation',  bg: 'bg-amber-50',  iconColor: 'text-amber-500',  icon: 'M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z', desc: 'Corrects brightness and contrast automatically so your face is evenly lit — even if your original selfie was dark.' },
  { title: 'Exact Pixel Dimensions',  bg: 'bg-rose-50',   iconColor: 'text-rose-500',   icon: 'M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15', desc: 'Every output is resized precisely — 600x600 for the US, 413x531 for the UK/EU, correct dimensions for every country.' },
  { title: 'AI Support Assistant',    bg: 'bg-cyan-50',   iconColor: 'text-cyan-600',   icon: 'M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z', desc: 'An intelligent bot reads your actual pipeline results and gives you specific, actionable guidance — not generic tips.' },
]

const PLANS = {
  starter: [
    { text: '3 photos per month',  included: true  },
    { text: 'All 5 countries',     included: true  },
    { text: 'Standard resolution', included: true  },
    { text: 'AI support chat',     included: true  },
    { text: 'No watermark',        included: false },
    { text: 'API access',          included: false },
  ],
  pro: [
    { text: 'Unlimited photos',               included: true  },
    { text: 'All countries + new ones first', included: true  },
    { text: 'HD resolution output',           included: true  },
    { text: 'No watermark',                   included: true  },
    { text: 'Priority AI support',            included: true  },
    { text: 'API access',                     included: false },
  ],
  business: [
    { text: 'Everything in Pro'           },
    { text: 'REST API access'             },
    { text: 'Bulk processing (50/batch)'  },
    { text: 'White-label output'          },
    { text: 'Dedicated support'           },
    { text: 'SLA guarantee'               },
  ],
}

const TESTIMONIALS = [
  { quote: 'I needed a passport photo fast and had no printer nearby. PassportAI did it in 10 seconds — looked more professional than my last official booth photo.', name: 'Sarah M.', role: 'Frequent traveller',            avatarBg: 'bg-indigo-500' },
  { quote: 'The compliance check is the killer feature. It told me exactly why my first photo failed and what to fix. Second attempt passed first time.',              name: 'James K.', role: 'Software developer',             avatarBg: 'bg-violet-500' },
  { quote: 'We integrated the Business API into our visa application portal. Background removal quality is excellent and the face ratio checks are spot on.',          name: 'Priya N.', role: 'Product manager, TravelTech',    avatarBg: 'bg-green-500'  },
]

const FAQS = [
  { q: 'Are the photos officially accepted?',       a: 'Yes. Each photo is validated against the official pixel dimensions and face-ratio rules published by the passport authority of the selected country. For US photos we target 600x600 px (2x2") at 300 DPI. Acceptance ultimately depends on your submission — we recommend reviewing your country\'s latest guidelines before submitting.' },
  { q: 'What makes a good input photo?',            a: 'Use a plain white or light-grey background, face the camera directly, ensure even lighting with no harsh shadows, keep a neutral expression with your mouth closed and eyes fully open, and remove glasses or hats. The closer your selfie is to these conditions, the faster it passes compliance.' },
  { q: 'Can I use glasses in my passport photo?',   a: 'Most countries (including the US, UK, and EU) no longer accept glasses in passport photos unless medically required. PassportAI will flag this if it detects eyewear, but the final check is the photographer\'s or authority\'s discretion.' },
  { q: 'Why did my photo fail compliance?',         a: 'The three most common reasons are: (1) face too small — move closer to the camera; (2) background not plain white — use a white wall with good lighting; (3) face not centred — look directly into the lens. Our AI support assistant will explain the specific reason and how to fix it.' },
  { q: 'How is my privacy protected?',              a: 'Uploaded photos are processed in memory and temporarily stored only to generate the download link. They are not retained permanently, not shared with third parties, and are automatically purged after a short period.' },
  { q: 'Can I use the API for my own application?', a: 'Yes — the Business plan includes REST API access with full pipeline results, bulk processing of up to 50 photos per batch, and white-label output. Contact us at the Business tier to get your API key and documentation.' },
  { q: 'What file formats and sizes are supported?', a: 'We accept JPG and PNG files up to 10 MB. For the best results, use the highest-resolution photo you have — minimum 600x600 px is recommended.' },
]

// ── File input & drag-drop ──────────────────────────────────────────────────
const ALLOWED   = ['image/jpeg', 'image/png']
const MAX_BYTES = 10 * 1024 * 1024

function onDrop(e) {
  dragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) handleFile(file)
}

function onFileInputChange(e) {
  const file = e.target.files?.[0]
  e.target.value = ''
  if (file) handleFile(file)
}

function handleFile(file) {
  if (!ALLOWED.includes(file.type)) return
  if (file.size > MAX_BYTES) return
  processFile(file)
}

function onCameraCaptured(file) {
  showCamera.value = false
  processFile(file)
}

// ── Core pipeline ───────────────────────────────────────────────────────────
async function processFile(file) {
  originalPreview.value  = URL.createObjectURL(file)
  processedPreview.value = null
  downloadUrl.value      = null
  currentStep.value      = 1
  status.value           = 'processing'

  const stepDelays = [0, 700, 1500, 2100, 2700, 3300, 3900]
  STEPS.forEach((step, i) => {
    setTimeout(() => { currentStep.value = step.id }, stepDelays[i])
  })

  const formData = new FormData()
  formData.append('image', file)
  formData.append('country', country.value)

  try {
    const { data } = await axios.post('/process-photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    currentStep.value = STEPS.length + 1
    if (data.success) {
      status.value           = 'success'
      processedPreview.value = data.downloadUrl
      downloadUrl.value      = data.downloadUrl
      pipelineErrors.value   = []
    } else {
      status.value         = 'error'
      pipelineErrors.value = data.errors ?? []
      console.error('[PassportAI] Pipeline failed', {
        message: data.message,
        errors:  data.errors,
        debug:   data.debug ?? null,
      })
    }
  } catch (err) {
    status.value         = 'error'
    currentStep.value    = STEPS.length + 1
    pipelineErrors.value = []
    const res = err?.response
    console.error('[PassportAI] Request failed', {
      status:  res?.status,
      message: res?.data?.message ?? err?.message,
      errors:  res?.data?.errors  ?? [],
      debug:   res?.data?.debug   ?? null,
      raw:     err,
    })
  }
}

// ── Modal helpers ─────────────────────────────────────────────────────────
function closeToolModal() {
  if (status.value !== 'processing') showToolModal.value = false
}

// ── Start over ──────────────────────────────────────────────────────────────
function startOver() {
  if (originalPreview.value) URL.revokeObjectURL(originalPreview.value)
  status.value           = 'idle'
  country.value          = 'US'
  currentStep.value      = 0
  originalPreview.value  = null
  processedPreview.value = null
  downloadUrl.value      = null
  pipelineErrors.value   = []
}
</script>

<style scoped>
html { scroll-behavior: smooth; }

/* Modal transition */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-active .modal-panel,
.modal-leave-active .modal-panel {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from .modal-panel,
.modal-leave-to .modal-panel {
  opacity: 0;
  transform: scale(0.96) translateY(8px);
}
</style>
