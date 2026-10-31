<script setup>
import { inject, useSlots, watchEffect, onBeforeUnmount } from 'vue';
import AppShellLayout from '@/Layouts/AppShellLayout.vue';

const slots = useSlots();
const shellSlots = inject('appShellSlots', null);
const instanceId = Symbol('authenticated-layout-instance');

if (shellSlots) {
    watchEffect(() => {
        shellSlots.ownerId = instanceId;
        shellSlots.header = slots.header || null;
        shellSlots.userMenuExtra = slots['user-menu-extra'] || null;
    });

    onBeforeUnmount(() => {
        if (shellSlots.ownerId === instanceId) {
            shellSlots.header = null;
            shellSlots.userMenuExtra = null;
            shellSlots.ownerId = null;
        }
    });
}
</script>

<template>
    <slot v-if="shellSlots" />
    <AppShellLayout v-else>
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>
        <template v-if="$slots['user-menu-extra']" #user-menu-extra>
            <slot name="user-menu-extra" />
        </template>
        <slot />
    </AppShellLayout>
</template>
