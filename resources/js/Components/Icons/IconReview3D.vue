<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 64 64"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    class="icon-review-3d"
    aria-hidden="true"
  >
    <defs>
      <!-- Filter bayangan lembut 3D -->
      <filter id="clayShadow" x="-15%" y="-15%" width="135%" height="135%" filterUnits="userSpaceOnUse">
        <feDropShadow dx="1.5" dy="3.5" stdDeviation="2.5" flood-color="#0f172a" flood-opacity="0.32" />
      </filter>
      <filter id="penShadow" x="-20%" y="-20%" width="140%" height="140%" filterUnits="userSpaceOnUse">
        <feDropShadow dx="1.2" dy="2.2" stdDeviation="1.8" flood-color="#091e42" flood-opacity="0.38" />
      </filter>

      <!-- Gradient Papan Biru (Clay 3D) -->
      <linearGradient id="boardGrad" x1="10" y1="12" x2="48" y2="58" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#4f8df7" />
        <stop offset="55%" stop-color="#2563eb" />
        <stop offset="100%" stop-color="#1d4ed8" />
      </linearGradient>
      <!-- Sisi tebal 3D papan -->
      <linearGradient id="boardExtrusion" x1="10" y1="52" x2="48" y2="60" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#1e40af" />
        <stop offset="100%" stop-color="#172554" />
      </linearGradient>

      <!-- Gradient Penjepit Atas -->
      <linearGradient id="clipGrad" x1="22" y1="6" x2="36" y2="16" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#475569" />
        <stop offset="100%" stop-color="#1e293b" />
      </linearGradient>

      <!-- Gradient Kertas Putih -->
      <linearGradient id="paperGrad" x1="14" y1="16" x2="44" y2="54" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#ffffff" />
        <stop offset="85%" stop-color="#f8fafc" />
        <stop offset="100%" stop-color="#e2e8f0" />
      </linearGradient>

      <!-- Gradient Pena Oranye 3D -->
      <linearGradient id="penBody" x1="40" y1="14" x2="56" y2="34" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#fb923c" />
        <stop offset="45%" stop-color="#f97316" />
        <stop offset="100%" stop-color="#ea580c" />
      </linearGradient>
      <linearGradient id="penAccent" x1="46" y1="10" x2="56" y2="22" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#38bdf8" />
        <stop offset="100%" stop-color="#0284c7" />
      </linearGradient>
      <linearGradient id="penTip" x1="33" y1="36" x2="38" y2="40" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#fdba74" />
        <stop offset="70%" stop-color="#f97316" />
        <stop offset="100%" stop-color="#1e293b" />
      </linearGradient>
    </defs>

    <!-- 1. Papan Clipboard Dasar (3D Base) -->
    <g filter="url(#clayShadow)">
      <!-- Ketebalan bawah 3D papan -->
      <rect x="12" y="14" width="36" height="45" rx="8" fill="url(#boardExtrusion)" />
      <!-- Muka utama papan -->
      <rect x="11" y="11" width="36" height="45" rx="8" fill="url(#boardGrad)" />
      <!-- Highlight lengkung atas papan -->
      <path d="M17 12C13.5 12 12 13.8 12 17" stroke="rgba(255,255,255,0.45)" stroke-width="1.8" stroke-linecap="round" />
    </g>

    <!-- 2. Kertas Putih (Curved Sheet) -->
    <g>
      <!-- Bayangan tipis kertas di atas papan -->
      <path d="M16 17H42C44.2 17 45.5 18.5 45.5 20.5V50C45.5 50.8 44.8 51.5 44 51.5H16C15 51.5 14 50.8 14 50V19C14 17.8 14.8 17 16 17Z" fill="rgba(15, 23, 42, 0.18)" />
      <!-- Kertas utama -->
      <path d="M16 16H42C43.8 16 45 17.2 45 19V49C45 49.8 44.2 50.5 43.5 50.5H16.5C15.5 50.5 14.5 49.8 14.5 49V17.5C14.5 16.8 15.2 16 16 16Z" fill="url(#paperGrad)" />
      <!-- Lekukan kertas bawah (efek 3D lembaran terangkat) -->
      <path d="M14.5 47C20 48.5 38 48 43.5 46.5" stroke="rgba(148, 163, 184, 0.4)" stroke-width="0.8" />
    </g>

    <!-- 3. Penjepit Atas Hitam/Abu Logam -->
    <g>
      <rect x="22" y="7" width="14" height="9" rx="3.5" fill="url(#clipGrad)" />
      <!-- Lubang penjepit -->
      <ellipse cx="29" cy="10" rx="3" ry="1.8" fill="#172554" opacity="0.65" />
      <ellipse cx="29" cy="9.8" rx="2.5" ry="1.4" fill="#ffffff" opacity="0.25" />
      <!-- Baut penjepit -->
      <circle cx="24.5" cy="13.2" r="1.1" fill="#94a3b8" />
      <circle cx="33.5" cy="13.2" r="1.1" fill="#94a3b8" />
    </g>

    <!-- 4. Checklist Item 1 (Checked) -->
    <g>
      <rect x="18" y="21.5" width="7" height="7" rx="2.2" fill="#3b82f6" />
      <path d="M19.8 25L21.5 26.6L23.8 23.4" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
      <!-- Garis teks orange 3D -->
      <rect x="27.5" y="23" width="12" height="2.6" rx="1.3" fill="#fb923c" />
      <rect x="27.5" y="27" width="8" height="2" rx="1" fill="#fca5a5" opacity="0.8" />
    </g>

    <!-- 5. Checklist Item 2 (Checked) -->
    <g>
      <rect x="18" y="31.5" width="7" height="7" rx="2.2" fill="#3b82f6" />
      <path d="M19.8 35L21.5 36.6L23.8 33.4" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
      <rect x="27.5" y="33" width="11" height="2.6" rx="1.3" fill="#fb923c" />
      <rect x="27.5" y="37" width="7" height="2" rx="1" fill="#cbd5e1" />
    </g>

    <!-- 6. Checklist Item 3 (Kotak proses) -->
    <g>
      <rect x="18" y="41.5" width="7" height="7" rx="2.2" fill="#e2e8f0" stroke="#60a5fa" stroke-width="1.4" />
      <rect x="27.5" y="43.5" width="10" height="2.6" rx="1.3" fill="#cbd5e1" />
    </g>

    <!-- 7. Pena Oranye 3D (Floating & Writing) -->
    <g filter="url(#penShadow)" transform="rotate(-15 44 26)">
      <!-- Klip pena biru -->
      <path d="M49 8H51.5C52.3 8 53 8.7 53 9.5V17C53 17.5 52.5 18 52 18H50" stroke="url(#penAccent)" stroke-width="2" stroke-linecap="round" />
      <!-- Tutup/Atas pena -->
      <ellipse cx="46.5" cy="8.5" rx="3.5" ry="2.2" fill="url(#penAccent)" />
      <!-- Cincin aksen biru -->
      <rect x="43" y="11" width="7" height="2.8" rx="1.2" fill="url(#penAccent)" />
      <!-- Badan utama pena (Oranye) -->
      <rect x="43.2" y="14" width="6.6" height="15" rx="3.2" fill="url(#penBody)" />
      <!-- Highlight kilap pena -->
      <path d="M44.5 15.5V26" stroke="rgba(255,255,255,0.55)" stroke-width="1.2" stroke-linecap="round" />
      <!-- Ujung runcing pena (Mata Pena) -->
      <path d="M43.5 28.5L46.5 36L49.5 28.5H43.5Z" fill="url(#penTip)" />
      <!-- Tinta runcing di ujung -->
      <circle cx="46.5" cy="35.5" r="0.9" fill="#0f172a" />
    </g>
  </svg>
</template>

<script setup>
defineProps({
  size: {
    type: [Number, String],
    default: 28,
  },
});
</script>

<style scoped>
.icon-review-3d {
  display: inline-block;
  vertical-align: middle;
  transform-origin: center center;
  transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.16));
}

:hover > .icon-review-3d,
.icon-review-3d:hover {
  transform: scale(1.18) rotate(-4deg);
  filter: drop-shadow(0 4px 8px rgba(37, 99, 235, 0.35));
}
</style>
