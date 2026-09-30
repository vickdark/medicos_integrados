<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarClock } from 'lucide-vue-next';
import AppointmentActions from '@/components/appointments/AppointmentActions.vue';
import AgendaList from '@/components/dashboard/AgendaList.vue';
import BarChart from '@/components/dashboard/BarChart.vue';
import StatTile from '@/components/dashboard/StatTile.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate, formatDateTime } from '@/lib/format';
import appointmentRoutes from '@/routes/appointments';
import consultationRoutes from '@/routes/consultations';
import patientRoutes from '@/routes/patients';
import type { DoctorInsights } from '@/types/models';

defineProps<{
    insights: DoctorInsights;
    role: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-6">
        <section
            v-if="insights.next_appointment"
            class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-brand-300 bg-brand-50 p-4 dark:border-brand-500/40 dark:bg-brand-500/10"
            aria-label="Próxima cita"
        >
            <div class="flex items-start gap-3">
                <div
                    class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white dark:bg-brand-400 dark:text-brand-950"
                >
                    <CalendarClock class="size-5" />
                </div>
                <div class="min-w-0">
                    <p
                        class="text-xs font-medium tracking-wide text-brand-800 uppercase dark:text-brand-300"
                    >
                        Tu próxima cita
                    </p>
                    <p class="text-lg font-semibold">
                        {{
                            formatDateTime(
                                insights.next_appointment.scheduled_at,
                            )
                        }}
                    </p>
                    <p class="text-sm">
                        <Link
                            v-if="insights.next_appointment.patient"
                            :href="
                                patientRoutes.show(
                                    insights.next_appointment.patient.id,
                                )
                            "
                            class="font-medium hover:underline"
                        >
                            {{ insights.next_appointment.patient.full_name }}
                        </Link>
                        · {{ insights.next_appointment.reason }}
                    </p>
                    <StatusBadge
                        class="mt-1"
                        :status="insights.next_appointment.status"
                    />
                </div>
            </div>
            <AppointmentActions
                :appointment="insights.next_appointment"
                :role="role"
            />
        </section>

        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Indicadores"
        >
            <StatTile
                label="Citas de hoy"
                :value="insights.kpis.appointments_today"
                :hint="
                    insights.kpis.appointments_today_open > 0
                        ? `${insights.kpis.appointments_today_open} por atender`
                        : 'Todas atendidas'
                "
            />
            <StatTile
                label="Solicitudes por confirmar"
                :value="insights.kpis.requests"
                :attention="insights.kpis.requests > 0"
                hint="Pendientes de confirmación"
                :href="
                    appointmentRoutes.index({ query: { status: 'requested' } })
                        .url
                "
            />
            <StatTile
                label="Consultas del mes"
                :value="insights.kpis.consultations_month"
                :delta="insights.kpis.consultations_delta"
                hint="Registradas este mes"
            />
            <StatTile
                label="Mis pacientes"
                :value="insights.kpis.patients"
                hint="Con cita o consulta contigo"
                :href="patientRoutes.index().url"
            />
        </section>

        <section class="grid gap-4 lg:grid-cols-2" aria-label="Tendencias">
            <BarChart
                title="Mi carga de los próximos 7 días"
                description="Citas solicitadas y confirmadas por día"
                unit="Citas"
                :data="insights.week_load"
            />
            <BarChart
                title="Consultas por mes"
                description="Consultas registradas en los últimos 6 meses"
                unit="Consultas"
                :data="insights.consultations_by_month"
            />
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Agenda de hoy</CardTitle>
                    <CardDescription
                        >Tus citas del día en orden</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <AgendaList
                        :appointments="insights.today_agenda"
                        :role="role"
                        empty-text="No tienes citas programadas para hoy."
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Solicitudes por confirmar</CardTitle>
                    <CardDescription>
                        Citas que los pacientes pidieron contigo
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <AgendaList
                        :appointments="insights.pending_requests"
                        :role="role"
                        with-date
                        empty-text="No tienes solicitudes pendientes."
                    />
                </CardContent>
            </Card>
        </section>

        <Card>
            <CardHeader>
                <CardTitle>Últimas consultas</CardTitle>
                <CardDescription>Lo último que registraste</CardDescription>
            </CardHeader>
            <CardContent>
                <p
                    v-if="insights.recent_consultations.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    Aún no has registrado consultas.
                </p>
                <ul v-else class="divide-y">
                    <li
                        v-for="consultation in insights.recent_consultations"
                        :key="consultation.id"
                        class="flex flex-wrap items-center justify-between gap-2 py-3"
                    >
                        <div class="min-w-0">
                            <Link
                                :href="
                                    patientRoutes.show(consultation.patient_id)
                                "
                                class="text-sm font-medium hover:underline"
                            >
                                {{ consultation.patient }}
                            </Link>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ formatDate(consultation.consulted_at) }} ·
                                {{ consultation.reason }}
                            </p>
                        </div>
                        <Button size="sm" variant="outline" as-child>
                            <Link
                                :href="consultationRoutes.show(consultation.id)"
                            >
                                Ver consulta
                            </Link>
                        </Button>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
