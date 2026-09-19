<script setup>
import { ref, computed, watch, onMounted } from 'vue';
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
    EditOutlined,
    SettingOutlined,
    AppstoreOutlined,
    EnvironmentOutlined,
    BankOutlined,
    TeamOutlined,
    LogoutOutlined,
    DownOutlined
} from '@ant-design/icons-vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const unreadCount = ref(page.props.unreadNotificationsCount || 0);
const { success, error } = useSweetAlert();
const { theme } = useTheme();
const isLightTheme = computed(() => theme.value === 'light');

watch(() => page.props.flash?.success, (message) => {
    if (message) success(message);
});

watch(() => page.props.flash?.error, (message) => {
    if (message) error(message);
});

const notifications = ref([]);
const userMenuOpen = ref(false);
const sidebarStateKey = 'arm-sidebar-collapsed';
const sidebarCollapsed = ref(
    typeof window !== 'undefined' && window.localStorage.getItem(sidebarStateKey) === 'true'
);

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem(sidebarStateKey, String(sidebarCollapsed.value));
};

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
        { name: 'Profil Saya', route: 'profile.edit', icon: UserOutlined, roles: ['KC', 'AM', 'ADMIN'] },
        { name: 'Memo Saya', route: 'memos.index', icon: FileTextOutlined, roles: ['KC'] },
        { name: 'Buat Memo', route: 'memos.create', icon: FormOutlined, roles: ['KC'] },
        { name: 'Kotak Masuk', route: 'approvals.pending', icon: InboxOutlined, roles: ['AM'] },
        { name: 'Tanda Tangan', route: 'signature.index', icon: EditOutlined, roles: ['AM'] },
        { name: 'Pengaturan TTD', route: 'signature.settings', icon: SettingOutlined, roles: ['AM'] },
        { name: 'Template Memo', route: 'admin.templates.index', icon: AppstoreOutlined, roles: ['ADMIN'] },
        { name: 'Kelola Area', route: 'admin.areas.index', icon: EnvironmentOutlined, roles: ['ADMIN'] },
        { name: 'Kelola Cabang', route: 'admin.branches.index', icon: BankOutlined, roles: ['ADMIN'] },
        { name: 'Kelola User', route: 'admin.users.index', icon: TeamOutlined, roles: ['ADMIN'] },
    ];
    return items.filter(item => item.roles.includes(role));
});

const selectedKeys = ref([]);

watch(() => route().current(), (currentRoute) => {
    if (currentRoute) {
        selectedKeys.value = [currentRoute];
    }
}, { immediate: true });

const onNavClick = (item) => {
    router.visit(route(item.route));
};

const fetchNotifications = async () => {
    try {
        const response = await fetch(route('notifications.index'));
        notifications.value = await response.json();
        unreadCount.value = notifications.value.filter(notification => !notification.is_read).length;
    } catch (e) {}
};

const markAsRead = async (notification) => {
    try {
        await fetch(route('notifications.read', notification.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content } });
        if (!notification.is_read) {
            notification.is_read = true;
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
        if (notification.memo_id) {
            router.visit(route('memos.show', notification.memo_id));
        }
    } catch (e) {}
};

const markAllRead = async () => {
    try {
        await fetch(route('notifications.readAll'), { method: 'POST', headers: { 'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content } });
        notifications.value.forEach(n => n.is_read = true);
        unreadCount.value = 0;
    } catch (e) {}
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
    <a-layout :style="{ height: '100vh', overflow: 'hidden', background: isLightTheme ? '#f8fafc' : '#0f172a' }">
        <!-- Sidebar -->
        <a-layout-sider
            v-model:collapsed="sidebarCollapsed"
            :trigger="null"
            collapsible
            theme="dark"
            :style="{ background: isLightTheme ? '#2474ad' : '#155080', height: '100vh', position: 'sticky', top: 0 }"
            :class="isLightTheme ? 'border-r border-blue-600' : 'border-r border-slate-800'"
            :width="280"
        >
            <div class="flex items-center gap-3 px-5 py-4 border-b border-blue-500/80">
                <img src="/logo-pgi.jpg" alt="Logo PGI" class="w-10 h-10 rounded-lg object-contain border border-blue-700 bg-white p-0.5 shadow-sm flex-shrink-0" />
                <div v-if="!sidebarCollapsed" class="flex flex-col min-w-0 text-white">
                    <span class="sidebar-brand-title text-xs font-bold text-white tracking-wider uppercase leading-tight">SISTEM LAYANAN<br>PENGAJUAN</span>
                    <div class="flex items-center gap-1.5 mt-1 text-[10px] text-blue-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-300 animate-pulse flex-shrink-0"></span>
                        <span class="font-medium tracking-wide truncate">{{ portalSubtitle }}</span>
                    </div>
                </div>
            </div>

            <a-menu
                v-model:selectedKeys="selectedKeys"
                mode="inline"
                theme="dark"
                :style="{ background: isLightTheme ? '#2474ad' : '#155080' }"
                class="border-r-0 pt-4"
            >
                <a-menu-item
                    v-for="item in navItems"
                    :key="item.route"
                    @click="onNavClick(item)"
                >
                    <template #icon>
                        <component :is="item.icon" />
                    </template>
                    {{ item.name }}
                </a-menu-item>
            </a-menu>
        </a-layout-sider>

        <!-- Main Content -->
        <a-layout class="main-layout" :style="{ background: isLightTheme ? '#f8fafc' : '#0f172a' }">
            <!-- Top Bar -->
            <a-layout-header
                class="px-6 sticky top-0 z-10 border-b"
                :style="{ padding: '0 24px', height: '64px', color: isLightTheme ? '#0f172a' : '#e2e8f0', background: isLightTheme ? '#ffffff' : '#17233a', backgroundColor: isLightTheme ? '#ffffff' : '#17233a', borderColor: isLightTheme ? '#e2e8f0' : '#334155' }"
            >
                <div class="header-left flex items-center gap-4" :class="isLightTheme ? 'text-slate-700' : 'text-slate-200'">
                    <menu-unfold-outlined
                        v-if="sidebarCollapsed"
                        class="text-lg cursor-pointer hover:text-blue-600 transition-colors flex-shrink-0"
                        @click="toggleSidebar"
                    />
                    <menu-fold-outlined
                        v-else
                        class="text-lg cursor-pointer hover:text-blue-600 transition-colors flex-shrink-0"
                        @click="toggleSidebar"
                    />
                    <div class="leading-none flex items-center min-w-0" :class="isLightTheme ? 'text-slate-800' : 'text-slate-100'">
                        <slot name="header" />
                    </div>
                </div>

                <div class="header-right flex items-center gap-3 flex-shrink-0 self-center" :class="isLightTheme ? 'text-slate-700' : 'text-slate-200'">
                    <div class="flex items-center justify-center">
                        <ThemeToggle />
                    </div>

                    <!-- Notifications -->
                    <a-dropdown trigger="['click']" placement="bottomRight" @openChange="handleNotificationDropdown">
                        <a-badge :count="unreadCount" :overflow-count="99" class="notification-badge cursor-pointer flex items-center justify-center">
                            <a-avatar shape="square" class="bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                                <template #icon><bell-outlined /></template>
                            </a-avatar>
                        </a-badge>
                        <template #overlay>
                            <div class="bg-white rounded-lg shadow-lg border border-gray-100 w-80 overflow-hidden">
                                <div class="flex justify-between items-center p-3 border-b border-gray-100 bg-gray-50">
                                    <span class="font-medium">Notifikasi</span>
                                    <a-button type="link" size="small" @click="markAllRead" class="text-xs">
                                        Tandai dibaca
                                    </a-button>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <a-empty v-if="notifications.length === 0" description="Tidak ada notifikasi" class="py-6" />
                                    <a-list v-else item-layout="horizontal" :data-source="notifications" class="px-2">
                                        <template #renderItem="{ item }">
                                            <a-list-item 
                                                class="cursor-pointer hover:bg-gray-50 rounded px-2 transition-colors border-b-0" 
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

                    <!-- User Menu -->
                    <a-dropdown placement="bottomRight" trigger="['click']" @openChange="handleUserDropdown">
                                <div class="user-menu-trigger flex items-center gap-2 cursor-pointer hover:bg-gray-50 px-2.5 py-1.5 rounded-lg transition-colors self-center">
                            <a-avatar size="small" class="bg-blue-500 flex items-center justify-center">
                                <template #icon><user-outlined /></template>
                            </a-avatar>
                            <span class="text-sm font-medium hidden sm:block leading-none">{{ user?.name }}</span>
                                    <down-outlined :class="['user-menu-arrow text-[10px] opacity-70', { 'is-open': userMenuOpen }]" />
                        </div>
                        <template #overlay>
                            <a-menu>
                                <a-menu-item key="info" disabled class="py-2">
                                    <div class="flex flex-col text-gray-800">
                                        <span class="font-medium">{{ user?.name }}</span>
                                        <span class="text-xs text-gray-500">{{ roleLabel }}</span>
                                    </div>
                                </a-menu-item>
                                <a-menu-divider />
                                <a-menu-item key="profile" @click="router.visit(route('profile.edit'))">
                                    <user-outlined class="mr-2" /> Profil Saya
                                </a-menu-item>
                                <a-menu-item key="logout" class="text-red-500" @click="handleLogout">
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
                class="rounded-lg shadow-sm border overflow-initial"
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
}
.ant-layout-content {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.7) transparent;
}
.ant-menu-item {
    border-radius: 8px !important;
    margin: 4px 8px !important;
    width: calc(100% - 16px) !important;
    font-weight: 600 !important;
}

.sidebar-brand-title {
    color: #ffffff !important;
    font-weight: 700 !important;
}
</style>
