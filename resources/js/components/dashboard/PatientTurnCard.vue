<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useIntervalFn } from '@vueuse/core';
import { BellRing, MonitorPlay, Ticket } from 'lucide-vue-next';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import turnRoutes from '@/routes/turns';

export type PatientTurn = {
    turn: {
        code: string;
        status: { value: string; label: string };
        doctor: string;
        ahead: number;
        serving: string | null;
    } | null;
    appointment: { scheduled_at: string; doctor: string } | null;
};

defineProps<{ myTurn: PatientTurn }>();

useIntervalFn(
    () => router.reload({ only: ['myTurn'] }),
    15_000,
);
</script>

<template>
    <Card
        :class="
            myTurn.turn?.status.value === 'called'
                ? 'border-sky-400 bg-sky-50 dark:border-sky-500/60 dark:bg-sky-500/10'
                : ''
        "
    >
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Ticket class="size-5" /> Mi turno de hoy
            </CardTitle>
            <CardDescription>
                Se actualiza solo; no necesitas recargar la página.
            </CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4 text-sm">
            <template v-if="myTurn.turn">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-5xl font-bold tracking-tight tabular-nums">
                        {{ myTurn.turn.code }}
                    </span>
                    <div class="grid gap-1">
                        <StatusBadge :status="myTurn.turn.status" />
                        <span class="text-muted-foreground">
                            {{ myTurn.turn.doctor }}
                        </span>
                    </div>
                </div>

                <p
                    v-if="myTurn.turn.status.value === 'called'"
                    class="flex items-center gap-2 text-base font-semibold text-sky-800 dark:text-sky-200"
                >
                    <BellRing class="size-5" /> ¡Es tu turno! Acércate al
                    consultorio de {{ myTurn.turn.doctor }}.
                </p>
                <p v-else-if="myTurn.turn.status.value === 'waiting'">
                    <template v-if="myTurn.turn.ahead === 0">
                        <strong>Eres el siguiente.</strong>
                    </template>
                    <template v-else>
                        Hay <strong>{{ myTurn.turn.ahead }}</strong>
                        {{ myTurn.turn.ahead === 1 ? 'persona' : 'personas' }}
                        antes que tú.
                    </template>
                    <template v-if="myTurn.turn.serving">
                        Ahora atienden el turno
                        <strong>{{ myTurn.turn.serving }}</strong>.
                    </template>
                </p>
                <p v-else class="text-muted-foreground">
                    Ya fuiste atendido. ¡Gracias por tu visita!
                </p>
            </template>

            <p v-else-if="myTurn.appointment">
                Tienes cita hoy,
                <strong>{{ formatDateTime(myTurn.appointment.scheduled_at) }}</strong>,
                con {{ myTurn.appointment.doctor }}. Al llegar a la clínica,
                acércate a recepción para que te asignen tu turno.
            </p>

            <div>
                <Button variant="outline" size="sm" as-child>
                    <a
                        :href="turnRoutes.board().url"
                        target="_blank"
                        rel="noopener"
                    >
                        <MonitorPlay /> Ver pantalla de turnos
                    </a>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
