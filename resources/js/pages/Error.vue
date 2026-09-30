<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Home, ShieldAlert } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

const props = defineProps<{ status: number }>();

const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'No tienes acceso a esta sección',
        description:
            'Tu rol no tiene permiso para ver este contenido. Si crees que es un error, consulta con el administrador de la clínica.',
    },
    404: {
        title: 'No encontramos lo que buscas',
        description: 'La página no existe o el registro ya no está disponible.',
    },
    419: {
        title: 'Tu sesión expiró',
        description: 'Recarga la página e inténtalo de nuevo.',
    },
    429: {
        title: 'Demasiados intentos',
        description: 'Espera un momento antes de volver a intentarlo.',
    },
};

const fallback = {
    title: 'Algo salió mal',
    description:
        'Tuvimos un problema al procesar tu solicitud. Inténtalo de nuevo en unos minutos.',
};

const message = computed(() => messages[props.status] ?? fallback);

function goBack() {
    if (window.history.length > 1) {
        window.history.back();

        return;
    }

    window.location.assign('/');
}
</script>

<template>
    <Head :title="message.title" />

    <div class="flex min-h-svh items-center justify-center bg-background p-6">
        <div class="flex max-w-md flex-col items-center gap-4 text-center">
            <div
                class="flex size-14 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
            >
                <ShieldAlert class="size-7" />
            </div>
            <p class="text-sm font-medium text-muted-foreground tabular-nums">
                Error {{ status }}
            </p>
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ message.title }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{ message.description }}
            </p>
            <div class="flex flex-wrap justify-center gap-2">
                <Button variant="outline" @click="goBack">
                    <ArrowLeft /> Volver
                </Button>
                <Button as-child>
                    <Link :href="home()"><Home /> Ir al inicio</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
