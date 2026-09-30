<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useIntervalFn } from '@vueuse/core';
import { HeartPulse, WifiOff } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { formatClock } from '@/lib/format';
import turnRoutes from '@/routes/turns';

type Board = {
    serving: {
        id: number;
        code: string;
        doctor: string;
        specialty: string;
        recent: boolean;
    }[];
    next: { id: number; code: string; doctor: string }[];
    waiting_count: number;
    updated_at: string;
};

const props = defineProps<{ board: Board }>();

const board = ref<Board>(props.board);
const offline = ref(false);
const now = ref(new Date());

async function refresh() {
    try {
        const response = await fetch(turnRoutes.feed().url, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        board.value = (await response.json()) as Board;
        offline.value = false;
    } catch {
        offline.value = true;
    }
}

useIntervalFn(refresh, 5_000);
useIntervalFn(() => (now.value = new Date()), 1_000);

const time = computed(() =>
    formatClock(now.value.getHours(), now.value.getMinutes()),
);
const today = computed(() =>
    now.value.toLocaleDateString('es', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    }),
);
</script>

<template>
    <Head title="Pantalla de turnos" />

    <div
        class="flex min-h-svh flex-col bg-background p-6 text-foreground lg:p-10"
    >
        <header class="flex items-center justify-between gap-4">
            <p
                class="flex items-center gap-3 text-2xl font-semibold lg:text-3xl"
            >
                <HeartPulse class="size-8 text-brand-600 dark:text-brand-400" />
                Médicos Integrados
            </p>
            <div class="flex items-center gap-4">
                <ThemeToggle />
                <div class="text-right">
                    <p class="text-3xl font-semibold tabular-nums lg:text-5xl">
                        {{ time }}
                    </p>
                    <p
                        class="text-sm text-muted-foreground capitalize lg:text-base"
                    >
                        {{ today }}
                    </p>
                </div>
            </div>
        </header>

        <main class="mt-8 grid flex-1 gap-8 lg:grid-cols-5">
            <section class="lg:col-span-3">
                <h2
                    class="text-lg font-semibold tracking-widest text-muted-foreground uppercase lg:text-xl"
                >
                    Atendiendo ahora
                </h2>
                <p
                    v-if="board.serving.length === 0"
                    class="mt-6 text-2xl text-muted-foreground"
                >
                    En un momento llamaremos al siguiente turno.
                </p>
                <ul class="mt-6 grid gap-4 sm:grid-cols-2">
                    <li
                        v-for="turn in board.serving"
                        :key="turn.id"
                        class="rounded-2xl border p-6 transition-colors"
                        :class="
                            turn.recent
                                ? 'animate-pulse border-brand-500 bg-brand-50 dark:border-brand-400 dark:bg-brand-500/20'
                                : 'bg-card'
                        "
                    >
                        <p
                            class="text-6xl font-bold tracking-tight tabular-nums lg:text-7xl"
                        >
                            {{ turn.code }}
                        </p>
                        <p class="mt-3 text-xl font-medium lg:text-2xl">
                            {{ turn.doctor }}
                        </p>
                        <p class="text-muted-foreground">
                            {{ turn.specialty }}
                        </p>
                    </li>
                </ul>
            </section>

            <section class="lg:col-span-2">
                <h2
                    class="text-lg font-semibold tracking-widest text-muted-foreground uppercase lg:text-xl"
                >
                    Próximos turnos
                    <span
                        v-if="board.waiting_count"
                        class="text-muted-foreground"
                    >
                        ({{ board.waiting_count }} en espera)
                    </span>
                </h2>
                <p
                    v-if="board.next.length === 0"
                    class="mt-6 text-2xl text-muted-foreground"
                >
                    No hay turnos en espera.
                </p>
                <ol class="mt-6 divide-y divide-border">
                    <li
                        v-for="turn in board.next"
                        :key="turn.id"
                        class="flex items-baseline justify-between gap-4 py-3"
                    >
                        <span
                            class="text-3xl font-semibold tabular-nums lg:text-4xl"
                        >
                            {{ turn.code }}
                        </span>
                        <span class="truncate text-lg text-muted-foreground">
                            {{ turn.doctor }}
                        </span>
                    </li>
                </ol>
            </section>
        </main>

        <footer
            class="mt-8 flex items-center justify-between gap-4 text-sm text-muted-foreground"
        >
            <p>Cuando veas tu turno, acércate al consultorio de tu médico.</p>
            <p
                v-if="offline"
                class="flex items-center gap-2 text-amber-600 dark:text-amber-400"
            >
                <WifiOff class="size-4" /> Sin conexión, reintentando…
            </p>
        </footer>
    </div>
</template>
