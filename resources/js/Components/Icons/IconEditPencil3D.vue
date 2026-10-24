<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 64 64"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    class="icon-editpencil-3d"
    aria-hidden="true"
  >
    <defs>
      <!-- Bayangan Lembut 3D -->
      <filter id="clayPenShadow" x="-25%" y="-25%" width="150%" height="150%" filterUnits="userSpaceOnUse">
        <feDropShadow dx="2" dy="3.5" stdDeviation="2.5" flood-color="#0f172a" flood-opacity="0.32" />
      </filter>

      <!-- Gradient Badan Pena Oranye Clay (Chubby 3D) -->
      <linearGradient id="clayBarrel" x1="24" y1="20" x2="40" y2="20" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#fb923c" />
        <stop offset="35%" stop-color="#f97316" />
        <stop offset="80%" stop-color="#ea580c" />
        <stop offset="100%" stop-color="#c2410c" />
      </linearGradient>

      <!-- Gradient Tutup Belakang Pena -->
      <linearGradient id="clayCap" x1="24" y1="6" x2="40" y2="6" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#fdba74" />
        <stop offset="40%" stop-color="#fb923c" />
        <stop offset="100%" stop-color="#ea580c" />
      </linearGradient>

      <!-- Gradient Aksen Biru Cerah (Cincin & Klip) -->
      <linearGradient id="clayBlue" x1="18" y1="10" x2="44" y2="24" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#60a5fa" />
        <stop offset="50%" stop-color="#2563eb" />
        <stop offset="100%" stop-color="#1d4ed8" />
      </linearGradient>

      <!-- Gradient Mata Pena (Nib) -->
      <linearGradient id="clayNib" x1="26" y1="46" x2="38" y2="58" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#fed7aa" />
        <stop offset="60%" stop-color="#fdba74" />
        <stop offset="100%" stop-color="#f97316" />
      </linearGradient>
    </defs>

    <!-- Seluruh pena digambar vertikal simetris sempurna di X=32, lalu diputar tepat 45 derajat -->
    <g filter="url(#clayPenShadow)" transform="rotate(-45 32 32)">
      <!-- 1. Klip Pena Biru (Pocket Clip di samping) -->
      <path
        d="M23 12H21C19.3 12 18 13.3 18 15V24C18 25.1 18.9 26 20 26H23"
        stroke="url(#clayBlue)"
        stroke-width="3.2"
        stroke-linecap="round"
        stroke-linejoin="round"
      />

      <!-- 2. Tutup Belakang Bulat (Rounded Cap) -->
      <path
        d="M25 15C25 10 27.5 7 32 7C36.5 7 39 10 39 15V18H25V15Z"
        fill="url(#clayCap)"
      />
      <!-- Kilau Tutup -->
      <ellipse cx="32" cy="9.5" rx="3.5" ry="1.5" fill="rgba(255,255,255,0.6)" />

      <!-- 3. Cincin Aksen Biru Atas (Top Accent Ring) -->
      <rect x="23.5" y="17.5" width="17" height="4.5" rx="2.25" fill="url(#clayBlue)" />

      <!-- 4. Badan Utama Pena Chubby Oranye (Main Barrel) -->
      <rect x="24.5" y="21" width="15" height="23" rx="4" fill="url(#clayBarrel)" />

      <!-- Kilau Specular 3D Memanjang Halus -->
      <path
        d="M28 23.5V41"
        stroke="rgba(255,255,255,0.6)"
        stroke-width="1.8"
        stroke-linecap="round"
      />

      <!-- 5. Cincin Pegangan Bawah Biru (Grip Collar Ring) -->
      <rect x="24" y="43" width="16" height="3.5" rx="1.75" fill="url(#clayBlue)" />

      <!-- 6. Ujung Kerucut Pena (Tapered Cone Nib) -->
      <path
        d="M25.5 46.5L32 56L38.5 46.5H25.5Z"
        fill="url(#clayNib)"
      />
      <!-- Highlight pada ujung kerucut -->
      <path
        d="M29 47.5L32 54"
        stroke="rgba(255,255,255,0.45)"
        stroke-width="1.2"
        stroke-linecap="round"
      />

      <!-- 7. Titik Tinta Runcing di Paling Ujung -->
      <path
        d="M30.8 54.2L32 56.5L33.2 54.2H30.8Z"
        fill="#0f172a"
      />
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
.icon-editpencil-3d {
  display: inline-block;
  vertical-align: middle;
  transform-origin: center center;
  transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.14));
}

:hover > .icon-editpencil-3d,
.icon-editpencil-3d:hover {
  transform: scale(1.18) rotate(5deg);
  filter: drop-shadow(0 4px 8px rgba(249, 115, 22, 0.35));
}
</style>
