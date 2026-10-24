<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 64 64"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    class="icon-docsheet-3d"
    aria-hidden="true"
  >
    <defs>
      <!-- Bayangan Lembut 3D -->
      <filter id="docSheetShadow" x="-20%" y="-20%" width="140%" height="140%" filterUnits="userSpaceOnUse">
        <feDropShadow dx="1.8" dy="3.8" stdDeviation="2.5" flood-color="#0f172a" flood-opacity="0.28" />
      </filter>
      <!-- Bayangan lipatan sudut kertas -->
      <filter id="foldShadow" x="-30%" y="-30%" width="160%" height="160%" filterUnits="userSpaceOnUse">
        <feDropShadow dx="-1" dy="1.5" stdDeviation="1.2" flood-color="#0f172a" flood-opacity="0.22" />
      </filter>
      <!-- Bayangan stempel segel -->
      <filter id="sealShadow" x="-20%" y="-20%" width="140%" height="140%" filterUnits="userSpaceOnUse">
        <feDropShadow dx="1" dy="1.8" stdDeviation="1.5" flood-color="#064e3b" flood-opacity="0.35" />
      </filter>

      <!-- Gradient Lembaran Kertas Putih Tebal 3D -->
      <linearGradient id="sheetGrad" x1="14" y1="10" x2="48" y2="54" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#ffffff" />
        <stop offset="60%" stop-color="#f8fafc" />
        <stop offset="100%" stop-color="#e2e8f0" />
      </linearGradient>

      <!-- Sisi Tebal 3D Kertas (Extrusion) -->
      <linearGradient id="sheetDepth" x1="14" y1="48" x2="52" y2="56" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#cbd5e1" />
        <stop offset="100%" stop-color="#94a3b8" />
      </linearGradient>

      <!-- Lipatan Sudut Atas Kertas (Fold) -->
      <linearGradient id="foldGrad" x1="38" y1="10" x2="49" y2="21" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#f1f5f9" />
        <stop offset="60%" stop-color="#cbd5e1" />
        <stop offset="100%" stop-color="#94a3b8" />
      </linearGradient>

      <!-- Stempel Segel Hijau Sukses (Verified/Completed) -->
      <linearGradient id="sealGrad" x1="32" y1="40" x2="46" y2="54" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#34d399" />
        <stop offset="70%" stop-color="#10b981" />
        <stop offset="100%" stop-color="#059669" />
      </linearGradient>
    </defs>

    <g filter="url(#docSheetShadow)">
      <!-- 1. Ketebalan Bawah 3D Lembaran Kertas -->
      <path
        d="M17 14H37L50 27V52C50 54.8 47.8 57 45 57H17C14.2 57 12 54.8 12 52V19C12 16.2 14.2 14 17 14Z"
        fill="url(#sheetDepth)"
      />

      <!-- 2. Muka Utama Dokumen Kertas 3D -->
      <path
        d="M16 11H37L49 23V49C49 51.8 46.8 54 44 54H16C13.2 54 11 51.8 11 49V16C11 13.2 13.2 11 16 11Z"
        fill="url(#sheetGrad)"
      />

      <!-- Kilau Lengkung Tepi Kiri Kertas -->
      <path
        d="M13 18V47"
        stroke="rgba(255,255,255,0.8)"
        stroke-width="1.8"
        stroke-linecap="round"
      />

      <!-- 3. Lipatan Sudut Kanan Atas (Dog-Ear Fold) -->
      <!-- Bayangan di bawah lipatan -->
      <path d="M37 11V21C37 22.1 37.9 23 39 23H49L37 11Z" fill="#94a3b8" opacity="0.3" />
      <!-- Muka lipatan kertas -->
      <g filter="url(#foldShadow)">
        <path
          d="M37 11V20C37 21.7 38.3 23 40 23H49L37 11Z"
          fill="url(#foldGrad)"
        />
      </g>

      <!-- 4. Garis Teks Dokumen (3D Text Lines) -->
      <!-- Baris Judul Biru (Accent Title) -->
      <rect x="17" y="19" width="16" height="3.5" rx="1.75" fill="#3b82f6" />

      <!-- Baris-baris Konten Dokumen (Soft Slate) -->
      <rect x="17" y="26.5" width="26" height="2.6" rx="1.3" fill="#cbd5e1" />
      <rect x="17" y="32" width="22" height="2.6" rx="1.3" fill="#cbd5e1" />
      <rect x="17" y="37.5" width="24" height="2.6" rx="1.3" fill="#cbd5e1" />
      <rect x="17" y="43" width="13" height="2.6" rx="1.3" fill="#cbd5e1" />

      <!-- 5. Stempel Segel Hijau Mini (Menunjukkan Dokumen Sah/Selesai) -->
      <g filter="url(#sealShadow)">
        <circle cx="39" cy="46" r="6" fill="url(#sealGrad)" />
        <!-- Tanda centang putih di dalam stempel -->
        <path
          d="M36.8 46.2L38.2 47.6L41.5 44.2"
          stroke="#ffffff"
          stroke-width="1.6"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        <!-- Kilau stempel -->
        <circle cx="37.5" cy="43.5" r="1.2" fill="#ffffff" opacity="0.65" />
      </g>
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
.icon-docsheet-3d {
  display: inline-block;
  vertical-align: middle;
  transform-origin: center center;
  transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.16));
}

:hover > .icon-docsheet-3d,
.icon-docsheet-3d:hover {
  transform: scale(1.18) rotate(-3deg);
  filter: drop-shadow(0 4px 8px rgba(37, 99, 235, 0.35));
}
</style>
