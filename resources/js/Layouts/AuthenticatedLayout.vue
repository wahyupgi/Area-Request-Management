<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const unreadCount = computed(() => page.props.unreadNotificationsCount || 0);

const showingNavDropdown = ref(false);
const showNotifications = ref(false);
const notifications = ref([]);
const sidebarCollapsed = ref(false);

const roleLabel = computed(() => {
    const labels = { KC: 'Kepala Cabang', AM: 'Area Manager', ADMIN: 'Administrator' };
    return labels[user.value?.role] || '';
});

const portalSubtitle = computed(() => {
    if (user.value?.role === 'ADMIN') return 'Portal Administrator';
    if (user.value?.role === 'AM') return 'Portal Area Manager';
    if (user.value?.role === 'KC') return 'Portal Kantor Cabang';
    return 'PT Pusat Gadai Indonesia';
});

const roleBadgeClass = computed(() => {
    const classes = {
        KC: 'bg-sky-400/20 text-sky-200 border border-sky-400/30',
        AM: 'bg-emerald-400/20 text-emerald-200 border border-emerald-400/30',
        ADMIN: 'bg-purple-400/20 text-purple-200 border border-purple-400/30'
    };
    return classes[user.value?.role] || '';
});

const navItems = computed(() => {
    const role = user.value?.role;
    const items = [
        { name: 'Dashboard', route: 'dashboard', icon: 'dashboard', roles: ['KC', 'AM', 'ADMIN'] },
        { name: 'Profil Saya', route: 'profile.edit', icon: 'profile', roles: ['KC', 'AM', 'ADMIN'] },
        { name: 'Memo Saya', route: 'memos.index', icon: 'memo', roles: ['KC'] },
        { name: 'Buat Memo', route: 'memos.create', icon: 'create', roles: ['KC'] },
        { name: 'Pending Approval', route: 'approvals.pending', icon: 'approval', roles: ['AM'] },
        { name: 'Tanda Tangan', route: 'signature.index', icon: 'signature', roles: ['AM'] },
        { name: 'Pengaturan TTD', route: 'signature.settings', icon: 'signature-settings', roles: ['AM'] },
        { name: 'Template Memo', route: 'admin.templates.index', icon: 'template', roles: ['ADMIN'] },
        { name: 'Kelola Area', route: 'admin.areas.index', icon: 'area', roles: ['ADMIN'] },
        { name: 'Kelola Cabang', route: 'admin.branches.index', icon: 'branch', roles: ['ADMIN'] },
        { name: 'Kelola User', route: 'admin.users.index', icon: 'users', roles: ['ADMIN'] },
    ];
    return items.filter(item => item.roles.includes(role));
});

const fetchNotifications = async () => {
    try {
        const response = await fetch(route('notifications.index'));
        notifications.value = await response.json();
    } catch (e) {}
};

const markAsRead = async (notification) => {
    try {
        await fetch(route('notifications.read', notification.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content } });
        notification.is_read = true;
        if (notification.memo_id) {
            router.visit(route('memos.show', notification.memo_id));
        }
    } catch (e) {}
};

const markAllRead = async () => {
    try {
        await fetch(route('notifications.readAll'), { method: 'POST', headers: { 'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content } });
        notifications.value.forEach(n => n.is_read = true);
    } catch (e) {}
};

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value;
    if (showNotifications.value) fetchNotifications();
};

const isActive = (routeName) => {
    try { return route().current(routeName); } catch { return false; }
};
</script>

<template>
    <div class="app-shell min-h-screen">
        <!-- Sidebar -->
        <aside :class="[sidebarCollapsed ? 'w-20' : 'w-72', 'fixed inset-y-0 left-0 z-30 flex flex-col transition-all duration-300 ease-in-out print:hidden']">
            <div class="sidebar-pgi flex flex-col h-full backdrop-blur-xl">
                <!-- Logo -->
                <div class="flex items-center gap-3 px-5 py-5 sidebar-divider border-b">
                    <img src="/logo-pgi.jpg" alt="Logo PGI" class="w-11 h-11 rounded-xl object-contain bg-white p-1 shadow-md ring-1 ring-white/20 flex-shrink-0" />
                    <div v-if="!sidebarCollapsed" class="flex flex-col min-w-0">
                        <span class="sidebar-brand-title text-xs font-bold tracking-wider uppercase leading-tight">SISTEM LAYANAN<br>PENGAJUAN</span>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse flex-shrink-0"></span>
                            <span class="sidebar-brand-subtitle text-[10px] font-medium tracking-wide truncate">{{ portalSubtitle }}</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        :class="[
                            isActive(item.route)
                                ? 'sidebar-nav-active'
                                : 'sidebar-nav-inactive',
                            sidebarCollapsed ? 'justify-center px-2' : 'px-3.5',
                            'sidebar-nav-item group flex items-center gap-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 border'
                        ]"
                        :title="sidebarCollapsed ? item.name : undefined"
                    >
                        <!-- Icons -->
                        <svg v-if="item.icon === 'dashboard'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <svg v-else-if="item.icon === 'memo'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <svg v-else-if="item.icon === 'create'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                        <svg v-else-if="item.icon === 'approval'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <svg v-else-if="item.icon === 'signature'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <svg v-else-if="item.icon === 'signature-settings'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94a1.5 1.5 0 012.812 0l.342 1.025a7.35 7.35 0 011.68.982l1.022-.35a1.5 1.5 0 011.987 1.987l-.35 1.022c.39.51.72 1.073.982 1.68l1.025.342a1.5 1.5 0 010 2.812l-1.025.342a7.35 7.35 0 01-.982 1.68l.35 1.022a1.5 1.5 0 01-1.987 1.987l-1.022-.35a7.35 7.35 0 01-1.68.982l-.342 1.025a1.5 1.5 0 01-2.812 0l-.342-1.025a7.35 7.35 0 01-1.68-.982l-1.022.35a1.5 1.5 0 01-1.987-1.987l.35-1.022a7.35 7.35 0 01-.982-1.68l-1.025-.342a1.5 1.5 0 010-2.812l1.025-.342a7.35 7.35 0 01.982-1.68l-.35-1.022a1.5 1.5 0 011.987-1.987l1.022.35a7.35 7.35 0 011.68-.982l.342-1.025z"/><circle cx="11" cy="12" r="2.5" stroke-width="1.5"/></svg>
                        <svg v-else-if="item.icon === 'template'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm10 0a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z"/></svg>
                        <svg v-else-if="item.icon === 'area'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <svg v-else-if="item.icon === 'branch'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <svg v-else-if="item.icon === 'users'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg v-else-if="item.icon === 'profile'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0112 19.5c-1.052 0-2.062-.133-3-.384m6-3.06a9.38 9.38 0 00-2.625-.372 9.37 9.37 0 00-2.625.372m5.25 0a24.73 24.73 0 01-1.5 3.06M9 19.128v-.003a9.37 9.37 0 012.625-.372M9 19.128v.106a12.318 12.318 0 003 .384m-3.75-9.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0z"/></svg>
                        <span v-if="!sidebarCollapsed">{{ item.name }}</span>
                    </Link>
                </nav>

                <!-- User Card -->
                <div class="p-4 sidebar-divider border-t">
                    <div :class="[sidebarCollapsed ? 'justify-center p-2' : 'px-3 py-2.5', 'sidebar-user-card flex items-center gap-3 rounded-xl border transition-all duration-200']">
                        <div class="sidebar-user-avatar w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold flex-shrink-0 shadow-sm ring-1 ring-white/10">
                            {{ user?.name?.charAt(0) }}
                        </div>
                        <div v-if="!sidebarCollapsed" class="flex-1 min-w-0">
                            <p class="sidebar-user-name text-sm font-semibold truncate">{{ user?.name }}</p>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span :class="[roleBadgeClass, 'text-[10px] font-medium px-2 py-0.5 rounded-md']">{{ roleLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div :class="[sidebarCollapsed ? 'pl-20' : 'pl-72', 'print:pl-0', 'transition-all duration-300']">
            <!-- Top Bar -->
            <header class="app-header-pgi sticky top-0 z-20 h-16 flex items-center justify-between px-6 backdrop-blur-xl print:hidden">
                <div class="flex items-center gap-4">
                    <button @click="sidebarCollapsed = !sidebarCollapsed" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div>
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <ThemeToggle />
                    <!-- Notification Bell -->
                    <div class="relative">
                        <button @click="toggleNotifications" class="relative p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <span v-if="unreadCount > 0" class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 rounded-full text-[10px] text-white flex items-center justify-center font-bold animate-pulse">
                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                            </span>
                        </button>

                        <!-- Notification Dropdown -->
                        <div v-if="showNotifications" class="absolute right-0 mt-2 w-96 bg-slate-800 border border-white/10 rounded-2xl shadow-2xl shadow-black/50 overflow-hidden z-50">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
                                <h3 class="text-sm font-semibold text-white">Notifikasi</h3>
                                <button @click="markAllRead" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">Tandai semua dibaca</button>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-slate-500">Tidak ada notifikasi</div>
                                <div
                                    v-for="notif in notifications"
                                    :key="notif.id"
                                    @click="markAsRead(notif)"
                                    :class="[!notif.is_read ? 'bg-indigo-500/5 border-l-2 border-l-indigo-500' : 'border-l-2 border-l-transparent', 'px-4 py-3 hover:bg-white/5 cursor-pointer transition-colors']"
                                >
                                    <p class="text-sm text-slate-300">{{ notif.message }}</p>
                                    <p class="text-[11px] text-slate-500 mt-1">{{ new Date(notif.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logout -->
                    <Link :href="route('logout')" method="post" as="button" class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </Link>
                </div>
            </header>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mx-6 mt-4 print:hidden">
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $page.props.flash.success }}
                </div>
            </div>
            <div v-if="$page.props.flash?.error" class="mx-6 mt-4 print:hidden">
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $page.props.flash.error }}
                </div>
            </div>

            <!-- Main Content -->
            <main class="p-6 print:p-0">
                <slot />
            </main>
        </div>

        <!-- Click outside to close notifications -->
        <div v-if="showNotifications" @click="showNotifications = false" class="fixed inset-0 z-10"></div>
    </div>
</template>
