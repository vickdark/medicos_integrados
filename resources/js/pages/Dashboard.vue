<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    CalendarPlus,
    ClipboardEdit,
    CircleHelp,
    UserPlus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DoctorDashboard from '@/components/dashboard/DoctorDashboard.vue';
import PatientTurnCard from '@/components/dashboard/PatientTurnCard.vue';
import type { PatientTurn } from '@/components/dashboard/PatientTurnCard.vue';
import StaffDashboard from '@/components/dashboard/StaffDashboard.vue';
import GuidedTour from '@/components/GuidedTour.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes';
import appointmentRoutes from '@/routes/appointments';
import consultationRoutes from '@/routes/consultations';
import patientRoutes from '@/routes/patients';
import tourRoutes from '@/routes/tour';
import type { BreadcrumbItem } from '@/types';
import type {
    Appointment,
    Consultation,
    DoctorInsights,
    RoleValue,
    StaffInsights,
} from '@/types/models';

const props = defineProps<{
    stats: { label: string; value: number | string }[];
    upcomingAppointments: Appointment[];
    recentConsultations: Consultation[];
    showTour: boolean;
    insights: StaffInsights | DoctorInsights | null;
    profileReminder: {
        patient_id: number;
        incomplete: boolean;
        locked: boolean;
    } | null;
    myTurn: PatientTurn | null;
}>();

const page = usePage();
const role = computed(() => page.props.auth.role?.value as RoleValue);
const fullName = computed(() => page.props.auth.user.name);

const tourOpen = ref(props.showTour);

function onTourToggle(open: boolean) {
    tourOpen.value = open;

    if (!open && props.showTour) {
        router.post(
            tourRoutes.store().url,
            {},
            { preserveScroll: true, preserveState: true },
        );
    }
}

const reminderNeedsAction = computed(
    () =>
        props.profileReminder !== null &&
        props.profileReminder.incomplete &&
        !props.profileReminder.locked,
);

const reminderButtonLabel = computed(() => {
    if (props.profileReminder?.locked) {
        return 'Actualizar mis datos';
    }

    return props.profileReminder?.incomplete
        ? 'Completar mis datos'
        : 'Revisar mis datos';
});

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Inicio', href: dashboard() }];
</script>

<template>
    <Head title="Inicio" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <GuidedTour
                v-if="role === 'patient'"
                :open="tourOpen"
                @update:open="onTourToggle"
            />

            <div
                v-if="profileReminder"
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4 text-sm"
                :class="
                    reminderNeedsAction
                        ? 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200'
                        : 'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-200'
                "
            >
                <p class="max-w-3xl">
                    <template v-if="profileReminder.locked">
                        <strong>Ya puedes actualizar tus datos.</strong>
                        Tus datos básicos quedaron registrados en tu primera
                        consulta. Puedes seguir editando tus datos personales y
                        de contacto (teléfono, dirección y contacto de
                        emergencia) cada vez que cambien.
                    </template>
                    <template v-else-if="profileReminder.incomplete">
                        <strong>Completa tus datos.</strong>
                        Nos faltan datos básicos de tu ficha (documento, fecha
                        de nacimiento, sexo, teléfono y grupo sanguíneo) para
                        atenderte mejor. Puedes completarlos hasta tu primera
                        consulta.
                    </template>
                    <template v-else>
                        <strong>Tienes tus datos al día.</strong>
                        Aún puedes revisarlos y editarlos, pero después de tu
                        primera consulta tus datos básicos ya no se podrán
                        modificar. Tus datos de contacto sí podrás actualizarlos
                        siempre.
                    </template>
                </p>
                <div class="flex items-center gap-2">
                    <Button size="sm" as-child>
                        <Link
                            :href="
                                patientRoutes.edit(profileReminder.patient_id)
                            "
                        >
                            <ClipboardEdit />
                            {{ reminderButtonLabel }}
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Hola, {{ fullName }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ page.props.auth.role?.label }} · Resumen de tu
                        actividad
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="role === 'patient'"
                        variant="outline"
                        @click="tourOpen = true"
                    >
                        <CircleHelp /> Ver visita guiada
                    </Button>
                    <Button
                        v-if="role === 'admin' || role === 'receptionist'"
                        variant="outline"
                        as-child
                    >
                        <Link :href="patientRoutes.create()">
                            <UserPlus /> Nuevo paciente
                        </Link>
                    </Button>
                    <Button v-if="role !== 'doctor'" as-child>
                        <Link :href="appointmentRoutes.create()">
                            <CalendarPlus />
                            {{
                                role === 'patient'
                                    ? 'Solicitar cita'
                                    : 'Agendar cita'
                            }}
                        </Link>
                    </Button>
                </div>
            </div>

            <PatientTurnCard
                v-if="role === 'patient' && myTurn"
                :my-turn="myTurn"
            />

            <StaffDashboard
                v-if="(role === 'admin' || role === 'receptionist') && insights"
                :insights="insights as StaffInsights"
                :role="role"
            />

            <DoctorDashboard
                v-else-if="role === 'doctor' && insights"
                :insights="insights as DoctorInsights"
                :role="role"
            />

            <div
                v-if="role === 'patient'"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <Card v-for="stat in stats" :key="stat.label" class="gap-2">
                    <CardHeader>
                        <CardDescription>{{ stat.label }}</CardDescription>
                        <CardTitle class="text-3xl tabular-nums">
                            {{ stat.value }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <div v-if="role === 'patient'" class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Próximas citas</CardTitle>
                        <CardDescription>
                            Citas solicitadas y confirmadas a partir de hoy
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="upcomingAppointments.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            No hay citas próximas.
                        </p>
                        <ul v-else class="divide-y">
                            <li
                                v-for="appointment in upcomingAppointments"
                                :key="appointment.id"
                                class="flex flex-wrap items-center justify-between gap-2 py-3"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium">
                                        {{
                                            formatDateTime(
                                                appointment.scheduled_at,
                                            )
                                        }}
                                    </p>
                                    <p
                                        class="truncate text-sm text-muted-foreground"
                                    >
                                        {{ appointment.doctor?.name }} ({{
                                            appointment.doctor?.specialty
                                        }}) · {{ appointment.reason }}
                                    </p>
                                </div>
                                <StatusBadge :status="appointment.status" />
                            </li>
                        </ul>
                        <div class="mt-4">
                            <Button variant="link" class="px-0" as-child>
                                <Link :href="appointmentRoutes.index()">
                                    Ver todas las citas →
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Últimas consultas</CardTitle>
                        <CardDescription>
                            Resumen de tu historia clínica
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="recentConsultations.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            Aún no tienes consultas registradas.
                        </p>
                        <ul v-else class="space-y-3">
                            <li
                                v-for="consultation in recentConsultations"
                                :key="consultation.id"
                            >
                                <Link
                                    :href="
                                        consultationRoutes.show(consultation.id)
                                    "
                                    class="block rounded-lg border p-3 transition-colors hover:bg-accent"
                                >
                                    <p class="text-sm font-medium">
                                        <span
                                            v-if="
                                                consultation.primary_diagnosis
                                            "
                                            class="font-mono text-xs text-muted-foreground"
                                            >{{
                                                consultation.primary_diagnosis
                                                    .code
                                            }}</span
                                        >
                                        {{ consultation.diagnosis }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            formatDate(
                                                consultation.consulted_at,
                                            )
                                        }}
                                        · {{ consultation.doctor?.name }}
                                    </p>
                                </Link>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
