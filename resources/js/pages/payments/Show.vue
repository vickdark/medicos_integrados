<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatDateTime, formatMoney } from '@/lib/format';
import patientRoutes from '@/routes/patients';
import paymentRoutes from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';
import type { Payment } from '@/types/models';

const props = defineProps<{
    payment: Payment;
    can: { view_patient: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pagos', href: paymentRoutes.index() },
    {
        title: `Pago #${props.payment.id}`,
        href: paymentRoutes.show(props.payment.id),
    },
];

const details = [
    { label: 'Concepto', value: props.payment.concept },
    { label: 'Método', value: props.payment.method.label },
    {
        label: 'Fecha de pago',
        value: props.payment.paid_at
            ? formatDate(props.payment.paid_at)
            : 'Aún sin pagar',
    },
    { label: 'Referencia', value: props.payment.reference },
    { label: 'Notas', value: props.payment.notes },
    { label: 'Registrado por', value: props.payment.recorded_by },
    { label: 'Creado', value: formatDateTime(props.payment.created_at) },
];
</script>

<template>
    <Head :title="`Pago #${payment.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Pago #{{ payment.id }}
                    </h1>
                    <div class="mt-2 flex items-center gap-3">
                        <span class="text-3xl font-semibold tabular-nums">
                            {{ formatMoney(payment.amount) }}
                        </span>
                        <StatusBadge :status="payment.status" />
                    </div>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="paymentRoutes.index()">
                        <ArrowLeft /> Volver a pagos
                    </Link>
                </Button>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Detalle del pago</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl class="grid gap-3 text-sm">
                            <div
                                v-for="detail in details"
                                :key="detail.label"
                                class="grid grid-cols-3 gap-2"
                            >
                                <dt class="text-muted-foreground">
                                    {{ detail.label }}
                                </dt>
                                <dd class="col-span-2 break-words">
                                    {{ detail.value || '—' }}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <div class="grid content-start gap-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Paciente</CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm">
                            <Link
                                v-if="payment.patient && can.view_patient"
                                :href="patientRoutes.show(payment.patient.id)"
                                class="font-medium hover:underline"
                            >
                                {{ payment.patient.full_name }}
                            </Link>
                            <span v-else class="font-medium">
                                {{ payment.patient?.full_name ?? '—' }}
                            </span>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Cita asociada</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-1 text-sm">
                            <template v-if="payment.appointment">
                                <p class="font-medium">
                                    {{
                                        formatDateTime(
                                            payment.appointment.scheduled_at,
                                        )
                                    }}
                                </p>
                                <p>
                                    {{ payment.appointment.doctor }} ·
                                    {{ payment.appointment.reason }}
                                </p>
                                <StatusBadge
                                    class="mt-1"
                                    :status="payment.appointment.status"
                                />
                            </template>
                            <p v-else class="text-muted-foreground">
                                Este pago no está asociado a una cita.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
