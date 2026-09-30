<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { usePushNotifications } from '@/composables/usePushNotifications';
import { edit as editNotifications } from '@/routes/notifications';

const DISMISSED_KEY = 'push-prompt-dismissed-at';
const SNOOZE_DAYS = 7;

const page = usePage();
const isPatient = computed(() => page.props.auth.role?.value === 'patient');
const onSettingsPage = computed(
    () => page.component === 'settings/Notifications',
);

const { supported, configured, permission, subscribed, ready, busy, subscribe } =
    usePushNotifications();

function wasDismissedRecently(): boolean {
    try {
        const dismissedAt = Number(localStorage.getItem(DISMISSED_KEY));

        return (
            dismissedAt > 0 &&
            Date.now() - dismissedAt < SNOOZE_DAYS * 24 * 60 * 60 * 1000
        );
    } catch {
        return false;
    }
}

const dismissed = ref(wasDismissedRecently());

const visible = computed(
    () =>
        isPatient.value &&
        !onSettingsPage.value &&
        supported &&
        configured.value &&
        ready.value &&
        permission.value === 'default' &&
        !subscribed.value &&
        !dismissed.value,
);

function dismiss() {
    dismissed.value = true;

    try {
        localStorage.setItem(DISMISSED_KEY, String(Date.now()));
    } catch {
        // The prompt simply comes back on the next visit.
    }
}
</script>

<template>
    <div
        v-if="visible"
        role="region"
        aria-label="Activar notificaciones"
        class="flex flex-wrap items-center gap-3 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200"
    >
        <Bell class="size-4 shrink-0" />
        <p class="min-w-56 flex-1">
            Activa las notificaciones para recibir un aviso en este dispositivo
            antes de tus citas.
            <Link
                :href="editNotifications()"
                class="underline underline-offset-4"
                >Más opciones</Link
            >
        </p>
        <div class="flex items-center gap-2">
            <Button size="sm" :disabled="busy" @click="subscribe">
                Activar
            </Button>
            <Button size="sm" variant="ghost" @click="dismiss">
                <X /> Ahora no
            </Button>
        </div>
    </div>
</template>
