<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { useTheme } from '@/composables/useTheme';
import {
    MenuUnfoldOutlined,
    MenuFoldOutlined,
    BellOutlined,
    UserOutlined,
    DashboardOutlined,
    FileTextOutlined,
    FormOutlined,
    InboxOutlined,
    AppstoreOutlined,
    BankOutlined,
    TeamOutlined,
    LogoutOutlined,
    DownOutlined,
    RightOutlined,
    BarChartOutlined,
    AuditOutlined,
    SettingOutlined,
} from '@ant-design/icons-vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const unreadCount = ref(page.props.unreadNotificationsCount || 0);
const isNavigating = ref(false);
let removeNavigationStart = null;
let removeNavigationFinish = null;
const { success, error } = useSweetAlert();
const { theme } = useTheme();
const isLightTheme = computed(() => theme.value === 'light');

watch(() => page.props.flash?.success, (message) => {
    if (message) success(message);
});

watch(() => page.props.flash?.error, (message) => {
    if (message) error(message);
});

watch(() => page.props.unreadNotificationsCount, (count) => {
    unreadCount.value = count || 0;
});

const notifications = ref([]);
const userMenuOpen = ref(false);
const sidebarStateKey = 'arm-sidebar-collapsed';
const sidebarCollapsed = ref(
    typeof window !== 'undefined' && window.localStorage.getItem(sidebarStateKey) === 'true'
);

const mobileQuery = '(max-width: 991.98px)';
const isMobile = ref(typeof window !== 'undefined' && window.matchMedia(mobileQuery).matches);
const mobileNavOpen = ref(false);
let mobileMediaList = null;
const onMobileChange = (event) => {
    isMobile.value = event.matches;
    if (!event.matches) mobileNavOpen.value = false;
};

const toggleSidebar = () => {
    if (isMobile.value) {
        mobileNavOpen.value = !mobileNavOpen.value;
        return;
    }
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem(sidebarStateKey, String(sidebarCollapsed.value));
};

const closeMobileNav = () => { mobileNavOpen.value = false; };
const effectiveCollapsed = computed(() => !isMobile.value && sidebarCollapsed.value);

watch(mobileNavOpen, (open) => {
    if (typeof document !== 'undefined') {
        document.body.classList.toggle('mobile-nav-open', open && isMobile.value);
    }
});

onMounted(() => {
    mobileMediaList = window.matchMedia(mobileQuery);
    isMobile.value = mobileMediaList.matches;
    mobileMediaList.addEventListener('change', onMobileChange);
    removeNavigationStart = router.on('start', () => { isNavigating.value = true; });
    removeNavigationFinish = router.on('finish', () => { isNavigating.value = false; });
});

onUnmounted(() => {
    mobileMediaList?.removeEventListener('change', onMobileChange);
    document.body.classList.remove('mobile-nav-open');
    removeNavigationStart?.();
    removeNavigationFinish?.();
});

const roleLabel = computed(() => {
    const labels = { KC: 'Kepala Cabang', AM: 'Area Manager', ADMIN: 'Administrator' };
    return labels[user.value?.role] || '';
});

const portalSubtitle = computed(() => {
    if (user.value?.role === 'ADMIN') return 'Administrator';
    if (user.value?.role === 'AM') return 'Area Manager';
    if (user.value?.role === 'KC') return 'Kantor Cabang';
    return 'PT Pusat Gadai Indonesia';
});

const roleBadgeClass = computed(() => {
    const classes = {
        KC: 'blue',
        AM: 'green',
        ADMIN: 'purple'
    };
    return classes[user.value?.role] || 'default';
});

const navItems = computed(() => {
    const role = user.value?.role;
    const items = [
        { name: 'Dashboard', route: 'dashboard', icon: DashboardOutlined, roles: ['KC', 'AM', 'ADMIN'] },
        { name: 'Cabang Saya', route: 'kc.branches.index', icon: BankOutlined, roles: ['KC'] },
        { name: 'Profil Saya', route: 'profile.edit', icon: UserOutlined, roles: ['KC', 'AM', 'ADMIN'] },
        {
            name: 'Pengajuan',
            key: 'submissions',
            icon: InboxOutlined,
            roles: ['KC'],
            children: [
                {
                    name: 'Internal Memo',
                    key: 'memo-submenu',
                    icon: FileTextOutlined,
                    children: [
                        { name: 'Buat Memo', route: 'memos.create', icon: FormOutlined },
                        { name: 'Riwayat Memo', route: 'memos.index', icon: FileTextOutlined },
                    ],
                },
                {
                    name: 'Berita Acara',
                    key: 'berita-acara-submenu',
                    icon: AuditOutlined,
                    children: [
                        { name: 'Buat BA', route: 'berita-acara.create', icon: FormOutlined },
                        { name: 'Riwayat BA', route: 'berita-acara.index', icon: FileTextOutlined },
                    ],
                },
                {
                    name: 'Form Pengajuan',
                    key: 'form-pengajuan-submenu',
                    icon: FormOutlined,
                    children: [
                        { name: 'Buat Form Pengajuan', route: 'form-pengajuan.create', icon: FormOutlined },
                        { name: 'Riwayat Form Pengajuan', route: 'form-pengajuan.index', icon: FileTextOutlined },
                    ],
                },
            ],
        },
        { name: 'Kotak Masuk', route: 'approvals.pending', icon: InboxOutlined, roles: ['AM'] },
        { name: 'Pengaturan TTD', route: 'signature.settings', icon: SettingOutlined, roles: ['AM'] },
        { name: 'Report GA', route: 'reports.ga', icon: BarChartOutlined, roles: ['AM'] },
        { name: 'Template Memo', route: 'admin.templates.index', icon: AppstoreOutlined, roles: ['ADMIN'] },
        { name: 'Kelola Cabang', route: 'admin.branch-assignments.index', icon: BankOutlined, roles: ['ADMIN'] },
        { name: 'Kelola User', route: 'admin.users.index', icon: TeamOutlined, roles: ['ADMIN'] },
    ];
    return items.filter(item => item.roles.includes(role));
});

const selectedKeys = ref([]);
const openKeys = ref([]);

const findActiveNavItem = (items, currentRoute, parentKeys = []) => {
    for (const item of items) {
        if (item.children) {
            const nestedItem = findActiveNavItem(item.children, currentRoute, [...parentKeys, item.key]);
            if (nestedItem) return nestedItem;
        } else if (
            item.route === currentRoute ||
            (item.route === 'memos.index' && ['memos.show', 'memos.edit'].includes(currentRoute))
        ) {
            return { selectedKey: item.route, parentKeys };
        }
    }

    return null;
};

const isFirstNavLoad = ref(true);

watch(() => route().current(), (currentRoute) => {
    if (currentRoute) {
        const activeItem = findActiveNavItem(navItems.value, currentRoute);
        selectedKeys.value = [activeItem?.selectedKey || currentRoute];
        // Hanya set openKeys saat pertama kali load,
        // selanjutnya user yang kontrol sendiri
        if (isFirstNavLoad.value) {
            openKeys.value = activeItem?.parentKeys || [];
            isFirstNavLoad.value = false;
        }
    }
}, { immediate: true });

let _navClicked = false;

const onNavClick = (item) => {
    closeMobileNav();
    _navClicked = true;
    nextTick(() => { _navClicked = false; });
    router.visit(route(item.route));
};

const onNavHover = (item) => {
    router.prefetch(route(item.route), { cacheFor: 30000 });
};

const onNavPress = (item) => {
    router.prefetch(route(item.route), { cacheFor: 30000 });
};

// Cegah accordion: submenu yang sudah terbuka tidak ditutup
// saat submenu lain dibuka atau saat navigasi ke leaf item.
const handleOpenChange = (newKeys) => {
    const addedKeys = newKeys.filter(k => !openKeys.value.includes(k));
    const removedKeys = openKeys.value.filter(k => !newKeys.includes(k));

    if (_navClicked) {
        // openChange dipicu oleh klik leaf item (navigasi) — abaikan removal
        openKeys.value = [...new Set([...openKeys.value, ...addedKeys])];
        return;
    }

    if (addedKeys.length > 0 && removedKeys.length > 0) {
        // Accordion mencoba nutup sibling → abaikan, tambah saja yang baru
        openKeys.value = [...openKeys.value, ...addedKeys];
    } else {
        // User sengaja nutup submenu → ikuti
        openKeys.value = newKeys;
    }
};

const fetchNotifications = async () => {
    try {
        const [notificationsResponse, countResponse] = await Promise.all([
            fetch(route('notifications.index')),
            fetch(route('notifications.unreadCount')),
        ]);
        if (!notificationsResponse.ok || !countResponse.ok) {
            throw new Error('Gagal memuat notifikasi.');
        }

        const [notificationList, countResult] = await Promise.all([
            notificationsResponse.json(),
            countResponse.json(),
        ]);
        notifications.value = notificationList;
        unreadCount.value = countResult.count;
    } catch (e) {
        error('Gagal memuat notifikasi.');
    }
};

const markAsRead = async (notification) => {
    try {
        const response = await fetch(route('notifications.read', notification.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content } });
        if (!response.ok) {
            throw new Error('Gagal menandai notifikasi telah dibaca.');
        }
        if (!notification.is_read) {
            notification.is_read = true;
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
        if (notification.memo_id) {
            router.visit(route('memos.show', notification.memo_id));
        }
    } catch (e) {
        error('Gagal menandai notifikasi telah dibaca.');
    }
};

const markAllRead = async () => {
    try {
        const response = await fetch(route('notifications.readAll'), { method: 'POST', headers: { 'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content } });
        if (!response.ok) {
            throw new Error('Gagal menandai semua notifikasi telah dibaca.');
        }
        notifications.value.forEach(n => n.is_read = true);
        unreadCount.value = 0;
    } catch (e) {
        error('Gagal menandai semua notifikasi telah dibaca.');
    }
};

const handleNotificationDropdown = (visible) => {
    if (visible) fetchNotifications();
};

const handleUserDropdown = (visible) => {
    userMenuOpen.value = visible;
};

const handleLogout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <a-layout class="app-shell" :style="{ height: '100vh', overflow: 'hidden', background: isLightTheme ? '#f8fafc' : '#0f172a' }">
        <div v-if="isNavigating" class="navigation-feedback" aria-live="polite" aria-label="Memuat halaman"></div>
        <!-- Sidebar -->
        <div v-if="isMobile && mobileNavOpen" class="sidebar-backdrop" @click="closeMobileNav"></div>
        <a-layout-sider
            :collapsed="effectiveCollapsed"
            :trigger="null"
            collapsible
            theme="dark"
            :style="{ background: isLightTheme ? '#2474ad' : '#155080', height: '100vh', position: 'sticky', top: 0 }"
            :class="[
                isLightTheme ? 'border-r border-blue-600' : 'border-r border-slate-800',
                { 'sidebar-is-collapsed': effectiveCollapsed, 'sidebar-mobile': isMobile, 'is-open': mobileNavOpen }
            ]"
            :width="280"
            :collapsedWidth="80"
        >
            <div class="flex items-center gap-3 px-5 py-4 border-b border-blue-500/80">
                <img src="/PGI-Primary Logo Flat.png" alt="Logo PGI" class="w-10 h-10 rounded-lg object-contain border border-blue-700 p-0.5 shadow-sm flex-shrink-0" />
                <div class="sidebar-brand-details flex flex-col min-w-0 text-white" :class="{ 'is-hidden': effectiveCollapsed }">
                    <span class="sidebar-brand-title text-xs font-bold text-white tracking-wider uppercase leading-tight">PUSAT GADAI INDONESIA</span>
                    <div class="flex items-center gap-1.5 mt-1 text-[10px] text-blue-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-300 animate-pulse flex-shrink-0"></span>
                        <span class="font-medium tracking-wide truncate">E-Memo AM Fathur</span>
                    </div>
                </div>
            </div>

            <a-menu
                v-model:selectedKeys="selectedKeys"
                :openKeys="openKeys"
                @openChange="handleOpenChange"
                mode="inline"
                theme="dark"
                :multiple="true"
                :style="{ background: isLightTheme ? '#2474ad' : '#155080' }"
                class="border-r-0 pt-4"
            >
                <template #expandIcon="{ isOpen }">
                    <RightOutlined :class="{ 'is-open': isOpen }" />
                </template>
                <template v-for="item in navItems" :key="item.key || item.route">
                    <a-sub-menu v-if="item.children" :key="item.key">
                        <template #icon>
                            <component :is="item.icon" />
                        </template>
                        <template #title>{{ item.name }}</template>

                        <template v-for="child in item.children" :key="child.key || child.route">
                            <a-sub-menu v-if="child.children" :key="child.key">
                                <template #icon>
                                    <component :is="child.icon" />
                                </template>
                                <template #title>{{ child.name }}</template>

                                <a-menu-item
                                    v-for="nestedItem in child.children"
                                    :key="nestedItem.route"
                                    @click="onNavClick(nestedItem)"
                                    @mouseenter="onNavHover(nestedItem)"
                                    @mousedown="onNavPress(nestedItem)"
                                    @touchstart.passive="onNavPress(nestedItem)"
                                >
                                    <template #icon>
                                        <component :is="nestedItem.icon" />
                                    </template>
                                    {{ nestedItem.name }}
                                </a-menu-item>
                            </a-sub-menu>

                            <a-menu-item
                                v-else
                                :key="child.route"
                                @click="onNavClick(child)"
                                @mouseenter="onNavHover(child)"
                                @mousedown="onNavPress(child)"
                                @touchstart.passive="onNavPress(child)"
                            >
                                <template #icon>
                                    <component :is="child.icon" />
                                </template>
                                {{ child.name }}
                            </a-menu-item>
                        </template>
                    </a-sub-menu>

                    <a-menu-item
                        v-else
                        :key="item.route"
                        @click="onNavClick(item)"
                        @mouseenter="onNavHover(item)"
                        @mousedown="onNavPress(item)"
                        @touchstart.passive="onNavPress(item)"
                    >
                        <template #icon>
                            <component :is="item.icon" />
                        </template>
                        {{ item.name }}
                    </a-menu-item>
                </template>
            </a-menu>
        </a-layout-sider>

        <!-- Main Content -->
        <a-layout class="main-layout app-main" :class="{ 'is-navigating': isNavigating }" :style="{ background: isLightTheme ? '#f8fafc' : '#0f172a' }">
            <!-- Top Bar -->
            <a-layout-header
                class="app-header px-6 sticky top-0 z-10 border-b"
                :style="{ padding: '0 24px', height: '64px', color: isLightTheme ? '#0f172a' : '#e2e8f0', background: isLightTheme ? '#ffffff' : '#17233a', backgroundColor: isLightTheme ? '#ffffff' : '#17233a', borderColor: isLightTheme ? '#e2e8f0' : '#334155' }"
            >
                <div class="header-left flex items-center gap-4 min-w-0" :class="isLightTheme ? 'text-slate-700' : 'text-slate-200'">
                    <menu-unfold-outlined
                        v-if="isMobile ? !mobileNavOpen : sidebarCollapsed"
                        class="header-toggle text-lg cursor-pointer hover:text-blue-600 transition-colors flex-shrink-0"
                        @click="toggleSidebar"
                    />
                    <menu-fold-outlined
                        v-else
                        class="header-toggle text-lg cursor-pointer hover:text-blue-600 transition-colors flex-shrink-0"
                        @click="toggleSidebar"
                    />
                    <div class="leading-none flex items-center min-w-0" :class="isLightTheme ? 'text-slate-800' : 'text-slate-100'">
                        <slot name="header" />
                    </div>
                </div>

                <div class="header-right flex items-center gap-3 flex-shrink-0 self-center" :class="isLightTheme ? 'text-slate-700' : 'text-slate-200'">
                    <div class="header-icon-group flex items-center gap-2">
                        <div class="header-icon-slot flex items-center justify-center">
                            <ThemeToggle />
                        </div>

                        <!-- Notifications -->
                        <div class="flex items-center self-center">
                        <a-dropdown trigger="['click']" placement="bottomRight" @openChange="handleNotificationDropdown" overlayClassName="header-notification-dropdown">
                            <div class="notif-icon-wrap cursor-pointer" :class="isLightTheme ? 'text-slate-600 hover:text-slate-900' : 'text-slate-300 hover:text-white'">
                                <bell-outlined class="text-xl transition-colors" />
                                <span v-if="unreadCount > 0" class="notif-count">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
                            </div>
                        <template #overlay>
                            <div class="notification-panel bg-white rounded-lg shadow-lg border border-gray-100 w-80 overflow-hidden">
                                <div class="notification-panel-header flex justify-between items-center p-3 border-b border-gray-100 bg-gray-50">
                                    <span class="notification-panel-title font-medium">Notifikasi</span>
                                    <a-button type="link" size="small" @click="markAllRead" class="notification-mark-read text-xs">
                                        Tandai dibaca
                                    </a-button>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <a-empty v-if="notifications.length === 0" description="Tidak ada notifikasi" class="py-6" />
                                    <a-list v-else item-layout="horizontal" :data-source="notifications" class="px-2">
                                        <template #renderItem="{ item }">
                                            <a-list-item 
                                                class="notification-item cursor-pointer hover:bg-gray-50 rounded px-2 transition-colors border-b-0" 
                                                :class="{'bg-blue-50/50': !item.is_read}"
                                                @click="markAsRead(item)"
                                            >
                                                <a-list-item-meta :description="new Date(item.created_at).toLocaleString('id-ID')">
                                                    <template #title>
                                                        <span class="text-sm font-normal" :class="{'font-medium text-blue-600': !item.is_read}">
                                                            {{ item.message }}
                                                        </span>
                                                    </template>
                                                    <template #avatar v-if="!item.is_read">
                                                        <a-badge dot status="processing" />
                                                    </template>
                                                </a-list-item-meta>
                                            </a-list-item>
                                        </template>
                                    </a-list>
                                </div>
                            </div>
                        </template>
                        </a-dropdown>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <a-dropdown placement="bottomRight" trigger="['click']" @openChange="handleUserDropdown" overlayClassName="header-user-dropdown">
                        <div class="user-menu-trigger flex items-center gap-2 cursor-pointer px-2.5 py-1.5 rounded-lg transition-colors self-center" :class="isLightTheme ? 'hover:bg-gray-50 text-slate-700' : 'hover:bg-slate-700/70 text-slate-200'">
                            <a-avatar size="small" class="bg-blue-500 flex items-center justify-center">
                                <template #icon><user-outlined /></template>
                            </a-avatar>
                            <span class="user-menu-name text-sm font-medium hidden sm:block leading-none">{{ user?.name }}</span>
                            <down-outlined :class="['user-menu-arrow text-[10px] opacity-70', { 'is-open': userMenuOpen }]" />
                        </div>
                        <template #overlay>
                            <a-menu>
                                <a-menu-item key="info" disabled class="py-2">
                                    <div class="flex flex-col" :class="isLightTheme ? 'text-gray-800' : 'text-slate-100'">
                                        <span class="font-medium">{{ user?.name }}</span>
                                        <span class="text-xs" :class="isLightTheme ? 'text-gray-500' : 'text-slate-400'">{{ roleLabel }}</span>
                                    </div>
                                </a-menu-item>
                                <a-menu-divider />
                                <slot name="user-menu-extra" />
                                <a-menu-divider v-if="$slots['user-menu-extra']" />
                                <a-menu-item key="profile" @click="router.visit(route('profile.edit'))">
                                    <user-outlined class="mr-2" /> Profil Saya
                                </a-menu-item>
                                <a-menu-item key="logout" class="user-menu-logout" @click="handleLogout">
                                    <logout-outlined class="mr-2" /> Keluar
                                </a-menu-item>
                            </a-menu>
                        </template>
                    </a-dropdown>
                </div>
            </a-layout-header>

            <!-- Main Content Area -->
            <a-layout-content
                :style="{ margin: '24px 16px', padding: '24px', background: isLightTheme ? '#ffffff' : '#1e293b', minHeight: '280px', height: 'calc(100vh - 64px - 48px)', overflowY: 'auto', borderColor: isLightTheme ? '#f3f4f6' : '#334155' }"
                class="app-content rounded-lg shadow-sm border overflow-initial"
            >
                <slot />
            </a-layout-content>
        </a-layout>
    </a-layout>
</template>

<style>
/* Adjust Ant Design layout variables slightly if needed */
.ant-layout-header {
    padding: 0 24px !important;
    background: #17233a !important;
    background-color: #17233a !important;
    border-bottom: 1px solid #334155 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    line-height: 1 !important;
}

html.theme-light .ant-layout-header {
    background: #ffffff !important;
    background-color: #ffffff !important;
    border-bottom-color: #e2e8f0 !important;
}

.header-left,
.header-right {
    display: flex;
    align-items: center;
    height: 100%;
}

.header-left {
    min-width: 0;
    height: 100%;
}

.header-left > .anticon {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 24px;
    height: 24px;
    line-height: 1 !important;
}

.header-left > div {
    display: flex;
    align-items: center;
    height: 100%;
}

.header-left h2 {
    margin: 0;
    line-height: 1.25;
}

.header-right {
    margin-left: auto;
    align-items: center;
    height: 100%;
}

.header-right > * {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.header-right .ant-dropdown,
.header-right .ant-dropdown-trigger,
.header-right .ant-badge {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.header-right .theme-toggle,
.header-right .ant-avatar {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    vertical-align: middle;
}

.header-right .ant-avatar {
    line-height: 1 !important;
}

.user-menu-arrow {
    transform: rotate(-90deg);
    transition: transform 0.2s ease;
}

.user-menu-arrow.is-open {
    transform: rotate(0deg);
}

.ant-layout-sider {
    overflow: hidden;
    transition: width 420ms cubic-bezier(0.22, 1, 0.36, 1) !important;
}

.ant-layout-sider .ant-layout-sider-children,
.ant-layout-sider .ant-menu,
.ant-layout-sider .ant-menu-inline-collapsed {
    transition: width 420ms cubic-bezier(0.22, 1, 0.36, 1) !important;
}

.sidebar-brand-details {
    max-width: 210px;
    opacity: 1;
    overflow: hidden;
    transform: translateX(0);
    transition: max-width 360ms ease, opacity 280ms ease, transform 360ms ease;
    white-space: nowrap;
}

.sidebar-brand-details.is-hidden {
    max-width: 0;
    opacity: 0;
    transform: translateX(-8px);
}
.ant-layout-content {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.5) transparent;
}
.ant-layout-content::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.ant-layout-content::-webkit-scrollbar-track {
    background: transparent;
}
.ant-layout-content::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.4);
    border-radius: 9999px;
}
.ant-layout-content::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.7);
}
.ant-menu-item {
    border-radius: 8px !important;
    margin: 4px 8px !important;
    width: calc(100% - 16px) !important;
    font-weight: 600 !important;
}

.ant-menu-submenu-title {
    font-family: inherit !important;
    font-size: 14px !important;
    font-weight: 600 !important;
}

.ant-menu-submenu-title .ant-menu-title-content {
    font-family: inherit !important;
    font-size: 14px !important;
    font-weight: 600 !important;
}

.ant-menu-submenu-expand-icon {
    transition: transform 0.2s ease;
}

.ant-menu-submenu-expand-icon.is-open {
    transform: rotate(90deg);
}

.sidebar-brand-title {
    color: #ffffff !important;
    font-weight: 700 !important;
}

.header-icon-group .ant-dropdown-trigger {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.notif-icon-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    margin-top: -3px;
    transition: background 0.2s;
}

.notif-icon-wrap:hover {
    background: rgba(100, 116, 139, 0.1);
}

.notif-count {
    position: absolute;
    top: 2px;
    right: 2px;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    line-height: 16px;
    border-radius: 999px;
    text-align: center;
    pointer-events: none;
    box-shadow: 0 0 0 1.5px #fff;
}

html:not(.theme-light) .header-notification-dropdown .notification-panel {
    background: #17233a !important;
    border-color: #334155 !important;
}

html:not(.theme-light) .header-notification-dropdown .notification-panel-header {
    background: #1e293b !important;
    border-color: #334155 !important;
}

html:not(.theme-light) .header-notification-dropdown .notification-panel-title,
html:not(.theme-light) .header-notification-dropdown .notification-item .ant-list-item-meta-title,
html:not(.theme-light) .header-notification-dropdown .notification-item .ant-list-item-meta-title > span {
    background: transparent !important;
    color: #e2e8f0 !important;
}

html:not(.theme-light) .header-notification-dropdown .notification-item {
    background: #1e293b !important;
    border-color: #334155 !important;
}

html:not(.theme-light) .header-notification-dropdown .notification-item.bg-blue-50\/50 {
    background: #1e3a5f !important;
}

html:not(.theme-light) .header-notification-dropdown .notification-item .ant-list-item-meta-description {
    background: transparent !important;
    color: #94a3b8 !important;
}

html:not(.theme-light) .header-notification-dropdown .notification-mark-read {
    background: transparent !important;
    color: #93c5fd !important;
}

html:not(.theme-light) .header-notification-dropdown .notification-item .text-blue-600 {
    color: #93c5fd !important;
}

html:not(.theme-light) .header-notification-dropdown .notification-item:hover {
    background: #334155 !important;
}

html:not(.theme-light) .header-notification-dropdown .notification-panel .ant-empty-description {
    color: #cbd5e1 !important;
}

html.theme-light .header-notification-dropdown .notification-panel {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
}

html.theme-light .header-notification-dropdown .notification-panel-header {
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
}

html.theme-light .header-notification-dropdown .notification-panel-title,
html.theme-light .header-notification-dropdown .notification-item .ant-list-item-meta-title,
html.theme-light .header-notification-dropdown .notification-item .ant-list-item-meta-title > span {
    color: #1e293b !important;
}

html.theme-light .header-notification-dropdown .notification-item {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
}

html.theme-light .header-notification-dropdown .notification-item.bg-blue-50\/50 {
    background: #eff6ff !important;
}

html.theme-light .header-notification-dropdown .notification-item .ant-list-item-meta-description {
    color: #64748b !important;
}

html.theme-light .header-notification-dropdown .notification-mark-read {
    color: #2563eb !important;
}

html.theme-light .header-notification-dropdown .notification-item:hover {
    background: #f8fafc !important;
}

html:not(.theme-light) .header-notification-dropdown,
html:not(.theme-light) .header-notification-dropdown .ant-dropdown-menu,
html:not(.theme-light) .header-notification-dropdown .ant-dropdown-menu-item,
html:not(.theme-light) .header-notification-dropdown .ant-dropdown-menu-item:hover,
html:not(.theme-light) .header-notification-dropdown .ant-list-item,
html:not(.theme-light) .header-notification-dropdown .ant-list-item-meta-title,
html:not(.theme-light) .header-notification-dropdown .ant-list-item-meta-description,
html:not(.theme-light) .header-notification-dropdown .ant-empty-description,
html:not(.theme-light) .header-notification-dropdown .ant-btn-link,
html:not(.theme-light) .header-user-dropdown,
html:not(.theme-light) .header-user-dropdown .ant-dropdown-menu,
html:not(.theme-light) .header-user-dropdown .ant-dropdown-menu-item,
html:not(.theme-light) .header-user-dropdown .ant-dropdown-menu-item:hover,
html:not(.theme-light) .header-user-dropdown .ant-dropdown-menu-item-divider,
html:not(.theme-light) .header-user-dropdown .ant-menu-item,
html:not(.theme-light) .header-user-dropdown .ant-menu-item * {
    background: #17233a !important;
    color: #e2e8f0 !important;
    border-color: rgba(148, 163, 184, 0.2) !important;
}

html:not(.theme-light) .header-notification-dropdown .ant-list-item:hover,
html:not(.theme-light) .header-notification-dropdown .ant-dropdown-menu-item:hover,
html:not(.theme-light) .header-user-dropdown .ant-dropdown-menu-item:hover,
html:not(.theme-light) .header-user-dropdown .ant-menu-item:hover {
    background: rgba(59, 130, 246, 0.12) !important;
}

html:not(.theme-light) .header-notification-dropdown .ant-btn-link,
html:not(.theme-light) .header-user-dropdown .ant-menu-item .text-red-500,
html:not(.theme-light) .header-user-dropdown .ant-menu-item.text-red-500 {
    color: #fca5a5 !important;
}

html:not(.theme-light) .header-user-dropdown .user-menu-logout,
html:not(.theme-light) .header-user-dropdown .user-menu-logout *,
html.theme-light .header-user-dropdown .user-menu-logout,
html.theme-light .header-user-dropdown .user-menu-logout * {
    color: #dc2626 !important;
}

html:not(.theme-light) .header-user-dropdown .ant-menu-item .anticon,
html:not(.theme-light) .header-user-dropdown .ant-menu-item .anticon + span,
html:not(.theme-light) .header-user-dropdown .ant-menu-item span,
html:not(.theme-light) .header-user-dropdown .ant-menu-item * {
    color: #e2e8f0 !important;
}

html:not(.theme-light) .ant-dropdown-menu,
html:not(.theme-light) .ant-dropdown-menu-item,
html:not(.theme-light) .ant-dropdown-menu-item * {
    color: #e2e8f0 !important;
}

html:not(.theme-light) .ant-dropdown-menu-item:hover,
html:not(.theme-light) .ant-dropdown-menu-item-selected {
    background: rgba(59, 130, 246, 0.12) !important;
}

html:not(.theme-light) .ant-menu-item,
html:not(.theme-light) .ant-menu-submenu-title,
html:not(.theme-light) .ant-menu-item * {
    color: #e2e8f0 !important;
}

html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker,
html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker:hover,
html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker:focus,
html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-focused {
    background: #1e293b !important;
    border-color: #475569 !important;
    box-shadow: none !important;
}

html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker input,
html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker .ant-picker-suffix {
    color: #e2e8f0 !important;
}

html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-dropdown,
html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-dropdown .ant-picker-panel-container,
html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-dropdown .ant-picker-header,
html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-dropdown .ant-picker-panel {
    background: #17233a !important;
    border-color: rgba(148, 163, 184, 0.2) !important;
}

html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-dropdown .ant-picker-header,
html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-dropdown .ant-picker-header button,
html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-dropdown .ant-picker-cell,
html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-dropdown .ant-picker-cell button {
    color: #e2e8f0 !important;
}

html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-dropdown .ant-picker-cell:hover:not(.ant-picker-cell-selected) .ant-picker-cell-inner {
    background: #334155 !important;
}

html:not(.theme-light) .header-user-dropdown .kc-dashboard-month-picker-dropdown .ant-picker-cell-selected .ant-picker-cell-inner {
    background: #2563eb !important;
    color: #ffffff !important;
}

html:not(.theme-light) .header-notification-dropdown .notification-mark-read {
    background: transparent !important;
    color: #93c5fd !important;
}

</style>
