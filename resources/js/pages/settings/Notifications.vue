<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Bell, BellOff } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { usePushNotifications } from '@/composables/usePushNotifications';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/notifications';
import type { BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notificaciones',
        href: edit(),
    },
];

const {
    supported,
    configured,
    permission,
    subscribed,
    busy,
    error,
    subscribe,
    unsubscribe,
} = usePushNotifications();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Notificaciones" />

        <h1 class="sr-only">Notificaciones</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Notificaciones en este dispositivo"
                    description="Recibe un aviso en tu navegador antes de tus citas, además del correo"
                />

                <p
                    v-if="!supported"
                    class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Este navegador no admite notificaciones push. En iPhone y
                    iPad primero debes agregar esta página a la pantalla de
                    inicio.
                </p>
                <p
                    v-else-if="!configured"
                    class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Las notificaciones push aún no están habilitadas en la
                    clínica.
                </p>
                <template v-else>
                    <p
                        v-if="permission === 'denied'"
                        class="rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200"
                    >
                        Bloqueaste las notificaciones de este sitio. Permítelas
                        desde la configuración del navegador para poder
                        activarlas.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            v-if="!subscribed"
                            :disabled="busy || permission === 'denied'"
                            @click="subscribe"
                        >
                            <Bell /> Activar notificaciones
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            :disabled="busy"
                            @click="unsubscribe"
                        >
                            <BellOff /> Desactivar notificaciones
                        </Button>
                        <p
                            v-if="subscribed"
                            class="text-sm text-emerald-700 dark:text-emerald-400"
                        >
                            Activadas en este dispositivo.
                        </p>
                    </div>

                    <p v-if="error" class="text-sm text-destructive">
                        {{ error }}
                    </p>
                </template>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
