<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AgendaList from '@/components/dashboard/AgendaList.vue';
import BarChart from '@/components/dashboard/BarChart.vue';
import StatTile from '@/components/dashboard/StatTile.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { toISODate } from '@/lib/calendar';
import { formatDateTime, formatMoney } from '@/lib/format';
import appointmentRoutes from '@/routes/appointments';
import paymentRoutes from '@/routes/payments';
import type { StaffInsights } from '@/types/models';

defineProps<{
    insights: StaffInsights;
    role: string;
}>();

const todayIso = toISODate(new Date());

const compactMoney = (value: number): string =>
    value >= 1000
        ? `${(value / 1000).toLocaleString('es', { maximumFractionDigits: 1 })} mil`
        : String(Math.round(value));
</script>

<template>
    <div class="flex flex-col gap-6">
        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5"
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
                :href="
                    appointmentRoutes.index({
                        query: {
                            from: todayIso,
                            to: todayIso,
                        },
                    }).url
                "
            />
            <StatTile
                label="Solicitudes por confirmar"
                :value="insights.kpis.requests"
                :attention="insights.kpis.requests > 0"
                hint="Esperan respuesta de la clínica"
                :href="
                    appointmentRoutes.index({ query: { status: 'requested' } })
                        .url
                "
            />
            <StatTile
                label="Ingresos del mes"
                :value="formatMoney(insights.kpis.income_month)"
                :delta="insights.kpis.income_delta"
                hint="Pagos cobrados este mes"
                :href="paymentRoutes.index({ query: { status: 'paid' } }).url"
            />
            <StatTile
                label="Por cobrar"
                :value="formatMoney(insights.kpis.pending_amount)"
                :attention="insights.kpis.pending_count > 0"
                :hint="`${insights.kpis.pending_count} ${insights.kpis.pending_count === 1 ? 'pago pendiente' : 'pagos pendientes'}`"
                :href="
                    paymentRoutes.index({ query: { status: 'pending' } }).url
                "
            />
            <StatTile
                label="Pacientes"
                :value="insights.kpis.patients"
                :hint="`${insights.kpis.patients_new_month} nuevos este mes`"
            />
        </section>

        <section class="grid gap-4 lg:grid-cols-2" aria-label="Tendencias">
            <BarChart
                title="Ingresos por mes"
                description="Pagos cobrados en los últimos 6 meses"
                unit="Ingresos"
                :data="insights.income_by_month"
                :format="compactMoney"
            />
            <BarChart
                title="Citas por día"
                description="Últimos 7 días y próximos 6 (sin canceladas)"
                unit="Citas"
                :data="insights.appointments_by_day"
            />
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Agenda de hoy</CardTitle>
                    <CardDescription>
                        Citas del día de todos los médicos
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <AgendaList
                        :appointments="insights.today_agenda"
                        :role="role"
                        show-doctor
                        empty-text="No hay citas programadas para hoy."
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Solicitudes por confirmar</CardTitle>
                    <CardDescription>
                        Ordenadas por la fecha de la cita
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <AgendaList
                        :appointments="insights.pending_requests"
                        :role="role"
                        show-doctor
                        with-date
                        empty-text="No hay solicitudes pendientes. ¡Todo al día!"
                    />
                    <div
                        v-if="
                            insights.kpis.requests >
                            insights.pending_requests.length
                        "
                        class="mt-3"
                    >
                        <Button variant="link" class="px-0" as-child>
                            <Link
                                :href="
                                    appointmentRoutes.index({
                                        query: { status: 'requested' },
                                    })
                                "
                            >
                                Ver las {{ insights.kpis.requests }} solicitudes
                                →
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </section>

        <section
            class="grid gap-4"
            :class="insights.top_doctors.length ? 'lg:grid-cols-2' : ''"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Cobros pendientes</CardTitle>
                    <CardDescription>
                        Los más antiguos primero; cóbralos desde Pagos
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="insights.pending_payments.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No hay cobros pendientes.
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="payment in insights.pending_payments"
                            :key="payment.id"
                            class="flex flex-wrap items-center justify-between gap-2 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ payment.patient }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ payment.concept }}
                                    <template v-if="payment.appointment_at">
                                        · Cita
                                        {{
                                            formatDateTime(
                                                payment.appointment_at,
                                            )
                                        }}
                                    </template>
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-semibold tabular-nums"
                                >
                                    {{ formatMoney(payment.amount) }}
                                </span>
                                <Button size="sm" variant="outline" as-child>
                                    <Link
                                        :href="
                                            paymentRoutes.index({
                                                query: { pay: payment.id },
                                            })
                                        "
                                    >
                                        Cobrar
                                    </Link>
                                </Button>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card v-if="insights.top_doctors.length">
                <CardHeader>
                    <CardTitle>Médicos con más citas este mes</CardTitle>
                    <CardDescription>Sin contar las canceladas</CardDescription>
                </CardHeader>
                <CardContent>
                    <ol class="grid gap-3">
                        <li
                            v-for="doctor in insights.top_doctors"
                            :key="doctor.id"
                            class="grid gap-1"
                        >
                            <div
                                class="flex items-baseline justify-between gap-2 text-sm"
                            >
                                <span class="truncate font-medium">
                                    {{ doctor.name }}
                                    <span
                                        class="font-normal text-muted-foreground"
                                    >
                                        · {{ doctor.specialty }}
                                    </span>
                                </span>
                                <span class="font-semibold tabular-nums">
                                    {{ doctor.appointments }}
                                </span>
                            </div>
                            <div
                                class="h-1.5 rounded-full bg-teal-100 dark:bg-teal-950"
                            >
                                <div
                                    class="h-full rounded-full bg-teal-600 dark:bg-teal-400"
                                    :style="{
                                        width: `${(doctor.appointments / insights.top_doctors[0].appointments) * 100}%`,
                                    }"
                                />
                            </div>
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
