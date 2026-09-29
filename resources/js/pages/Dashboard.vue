<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarPlus, UserPlus } from 'lucide-vue-next';
import { computed } from 'vue';
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
import type { BreadcrumbItem } from '@/types';
import type { Appointment, Consultation, RoleValue } from '@/types/models';

defineProps<{
    stats: { label: string; value: number | string }[];
    upcomingAppointments: Appointment[];
    recentConsultations: Consultation[];
}>();

const page = usePage();
const role = computed(() => page.props.auth.role?.value as RoleValue);
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Inicio', href: dashboard() }];
</script>

<template>
    <Head title="Inicio" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Hola, {{ firstName }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ page.props.auth.role?.label }} · Resumen de tu
                        actividad
                    </p>
                </div>
                <div class="flex gap-2">
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

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.label" class="gap-2">
                    <CardHeader>
                        <CardDescription>{{ stat.label }}</CardDescription>
                        <CardTitle class="text-3xl tabular-nums">
                            {{ stat.value }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
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
                                        <template v-if="role !== 'patient'">
                                            {{ appointment.patient?.full_name }}
                                            ·
                                        </template>
                                        <template v-if="role !== 'doctor'">
                                            {{ appointment.doctor?.name }} ({{
                                                appointment.doctor?.specialty
                                            }}) ·
                                        </template>
                                        {{ appointment.reason }}
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

                <Card v-if="role === 'patient'">
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

                <Card v-else>
                    <CardHeader>
                        <CardTitle>Accesos rápidos</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-2">
                        <Button
                            variant="outline"
                            class="justify-start"
                            as-child
                        >
                            <Link :href="patientRoutes.index()"
                                >Buscar paciente</Link
                            >
                        </Button>
                        <Button
                            variant="outline"
                            class="justify-start"
                            as-child
                        >
                            <Link
                                :href="
                                    appointmentRoutes.index({
                                        query: { status: 'requested' },
                                    })
                                "
                            >
                                Solicitudes por confirmar
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
