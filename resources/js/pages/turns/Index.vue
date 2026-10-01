<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { useIntervalFn } from '@vueuse/core';
import {
    CircleCheck,
    Megaphone,
    MonitorPlay,
    Plus,
    Stethoscope,
    Ticket,
    Undo2,
    UserPlus,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import TurnController from '@/actions/App/Http/Controllers/TurnController';
import IconButton from '@/components/IconButton.vue';
import InputError from '@/components/InputError.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction } from '@/lib/confirm';
import { formatClock } from '@/lib/format';
import consultationRoutes from '@/routes/consultations';
import patientRoutes from '@/routes/patients';
import turnRoutes from '@/routes/turns';
import type { BreadcrumbItem } from '@/types';
import type { Option } from '@/types/models';

type TurnStatusValue = 'waiting' | 'called' | 'done' | 'cancelled';

type TurnRow = {
    id: number;
    code: string;
    status: Option & { value: TurnStatusValue };
    patient: { id: number; full_name: string; document_number: string | null };
    doctor: string;
    appointment_id: number | null;
    appointment_at: string | null;
    arrived_at: string | null;
    called_at: string | null;
    ahead: number;
    can: { update_status: boolean; attend: boolean };
};

const props = defineProps<{
    turns: TurnRow[];
    summary: Record<TurnStatusValue, number>;
    pendingAppointments: {
        id: number;
        scheduled_at: string;
        patient: string;
        doctor: string;
    }[];
    patients: { value: number; label: string }[];
    doctors: { value: number; label: string }[];
    can: { create: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Turnos', href: turnRoutes.index() },
];

useIntervalFn(
    () =>
        router.reload({
            only: ['turns', 'summary', 'pendingAppointments'],
            headers: { 'X-Background': '1' },
        }),
    15_000,
);

const doctorFilter = ref('');
const doctorNames = computed(() =>
    [...new Set(props.turns.map((turn) => turn.doctor))].sort(),
);
const visibleTurns = computed(() =>
    doctorFilter.value
        ? props.turns.filter((turn) => turn.doctor === doctorFilter.value)
        : props.turns,
);

const clock = (value: string | null) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return formatClock(date.getHours(), date.getMinutes());
};

function changeStatus(turn: TurnRow, status: TurnStatusValue) {
    router.patch(
        turnRoutes.status(turn.id).url,
        { status },
        { preserveScroll: true },
    );
}

async function cancel(turn: TurnRow) {
    const accepted = await confirmAction({
        title: `Cancelar turno ${turn.code}`,
        text: `${turn.patient.full_name} saldrá de la fila.`,
        confirmText: 'Sí, cancelar',
        cancelText: 'Volver',
        tone: 'danger',
    });

    if (accepted) {
        changeStatus(turn, 'cancelled');
    }
}

function checkIn(appointmentId: number) {
    router.post(
        turnRoutes.store().url,
        { appointment_id: appointmentId },
        { preserveScroll: true },
    );
}

const newTurnOpen = ref(false);

const summaryCards = computed(() => [
    { label: 'En espera', value: props.summary.waiting },
    { label: 'En atención', value: props.summary.called },
    { label: 'Atendidos', value: props.summary.done },
]);
</script>

<template>
    <Head title="Turnos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Turnos de hoy
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        La lista se actualiza sola cada pocos segundos.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" as-child>
                        <a
                            :href="turnRoutes.board().url"
                            target="_blank"
                            rel="noopener"
                        >
                            <MonitorPlay /> Pantalla de turnos
                        </a>
                    </Button>
                    <Button v-if="can.create" @click="newTurnOpen = true">
                        <Plus /> Nuevo turno
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3 lg:max-w-3xl">
                <Card
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="gap-2"
                >
                    <CardHeader>
                        <CardDescription>{{ card.label }}</CardDescription>
                        <CardTitle class="text-2xl tabular-nums">
                            {{ card.value }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <Card v-if="can.create && pendingAppointments.length">
                <CardHeader>
                    <CardTitle>Citas de hoy sin turno</CardTitle>
                    <CardDescription>
                        Cuando el paciente llegue, genérale su turno para que
                        entre a la fila de su médico.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="divide-y rounded-lg border">
                        <li
                            v-for="appointment in pendingAppointments"
                            :key="appointment.id"
                            class="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5 text-sm"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ appointment.patient }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ clock(appointment.scheduled_at) }} ·
                                    {{ appointment.doctor }}
                                </p>
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="checkIn(appointment.id)"
                            >
                                <Ticket /> Generar turno
                            </Button>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <div
                v-if="doctorNames.length > 1"
                class="grid w-full gap-1 sm:w-64"
            >
                <Label for="turn-doctor" class="text-xs text-muted-foreground">
                    Médico
                </Label>
                <NativeSelect id="turn-doctor" v-model="doctorFilter">
                    <option value="">Todos</option>
                    <option
                        v-for="doctor in doctorNames"
                        :key="doctor"
                        :value="doctor"
                    >
                        {{ doctor }}
                    </option>
                </NativeSelect>
            </div>

            <div class="overflow-x-auto rounded-lg border">
                <table class="cards w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Turno</th>
                            <th class="px-4 py-3 font-medium">Paciente</th>
                            <th class="px-4 py-3 font-medium">Médico</th>
                            <th class="px-4 py-3 font-medium">Llegada</th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                            <th class="px-4 py-3 font-medium">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="visibleTurns.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                Aún no hay turnos hoy.
                            </td>
                        </tr>
                        <tr
                            v-for="turn in visibleTurns"
                            :key="turn.id"
                            :class="{
                                'bg-sky-50/60 dark:bg-sky-500/5':
                                    turn.status.value === 'called',
                                'opacity-60':
                                    turn.status.value === 'done' ||
                                    turn.status.value === 'cancelled',
                            }"
                        >
                            <td
                                data-label="Turno"
                                class="px-4 py-3 text-lg font-semibold tabular-nums"
                            >
                                {{ turn.code }}
                            </td>
                            <td data-label="Paciente" class="px-4 py-3">
                                <Link
                                    :href="patientRoutes.show(turn.patient.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ turn.patient.full_name }}
                                </Link>
                                <p
                                    v-if="turn.patient.document_number"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ turn.patient.document_number }}
                                </p>
                            </td>
                            <td data-label="Médico" class="px-4 py-3">
                                {{ turn.doctor }}
                            </td>
                            <td data-label="Llegada" class="px-4 py-3">
                                {{ clock(turn.arrived_at) }}
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        turn.appointment_at
                                            ? `Cita ${clock(turn.appointment_at)}`
                                            : 'Sin cita'
                                    }}
                                </p>
                            </td>
                            <td data-label="Estado" class="px-4 py-3">
                                <StatusBadge :status="turn.status" />
                                <p
                                    v-if="turn.status.value === 'waiting'"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{
                                        turn.ahead === 0
                                            ? 'Es el siguiente'
                                            : `${turn.ahead} delante`
                                    }}
                                </p>
                            </td>
                            <td data-label="" class="px-4 py-3">
                                <div
                                    v-if="turn.can.update_status"
                                    class="flex justify-end gap-1.5"
                                >
                                    <IconButton
                                        v-if="turn.can.attend"
                                        label="Atender (registrar consulta)"
                                        tone="violet"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                consultationRoutes.create(
                                                    turn.patient.id,
                                                    {
                                                        query: turn.appointment_id
                                                            ? {
                                                                  appointment_id:
                                                                      turn.appointment_id,
                                                              }
                                                            : {},
                                                    },
                                                )
                                            "
                                        >
                                            <Stethoscope />
                                        </Link>
                                    </IconButton>
                                    <IconButton
                                        v-if="turn.status.value === 'waiting'"
                                        label="Llamar"
                                        tone="info"
                                        @click="changeStatus(turn, 'called')"
                                    >
                                        <Megaphone />
                                    </IconButton>
                                    <IconButton
                                        v-if="turn.status.value === 'called'"
                                        label="Marcar como atendido"
                                        tone="success"
                                        @click="changeStatus(turn, 'done')"
                                    >
                                        <CircleCheck />
                                    </IconButton>
                                    <IconButton
                                        v-if="turn.status.value === 'called'"
                                        label="Devolver a la fila"
                                        tone="warning"
                                        @click="changeStatus(turn, 'waiting')"
                                    >
                                        <Undo2 />
                                    </IconButton>
                                    <IconButton
                                        label="Cancelar turno"
                                        tone="danger"
                                        @click="cancel(turn)"
                                    >
                                        <X />
                                    </IconButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Dialog v-model:open="newTurnOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Nuevo turno</DialogTitle>
                        <DialogDescription>
                            Para pacientes que llegan sin cita. Si el paciente
                            tiene cita hoy, usa «Generar turno» en la lista de
                            citas.
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        v-bind="TurnController.store.form()"
                        class="grid gap-4"
                        :options="{ preserveScroll: true }"
                        v-slot="{ errors, processing }"
                        @success="newTurnOpen = false"
                    >
                        <div class="grid gap-2">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <Label for="turn-patient">Paciente *</Label>
                                <Link
                                    :href="patientRoutes.create()"
                                    class="inline-flex items-center gap-1 text-xs text-primary hover:underline"
                                >
                                    <UserPlus class="size-3.5" /> Registrar
                                    paciente nuevo
                                </Link>
                            </div>
                            <SearchableSelect
                                id="turn-patient"
                                name="patient_id"
                                :options="patients"
                                placeholder="Selecciona un paciente"
                                search-placeholder="Buscar por nombre o documento"
                                required
                            />
                            <InputError :message="errors.patient_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="turn-doctor-select">Médico *</Label>
                            <SearchableSelect
                                id="turn-doctor-select"
                                name="doctor_id"
                                :options="doctors"
                                placeholder="Selecciona el médico"
                                search-placeholder="Buscar por nombre o especialidad"
                                required
                            />
                            <InputError :message="errors.doctor_id" />
                        </div>
                        <Button :disabled="processing">
                            <Ticket /> Generar turno
                        </Button>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
