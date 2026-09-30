<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    CalendarPlus,
    ClipboardList,
    Eye,
    FilePlus2,
    Pencil,
    ShieldCheck,
    TriangleAlert,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';
import IconButton from '@/components/IconButton.vue';
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
import { formatDate, formatDateTime, formatMoney } from '@/lib/format';
import appointmentRoutes from '@/routes/appointments';
import auditLogRoutes from '@/routes/audit-logs';
import consultationRoutes from '@/routes/consultations';
import patientRoutes from '@/routes/patients';
import paymentRoutes from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';
import type {
    Appointment,
    Consultation,
    Patient,
    Payment,
} from '@/types/models';

const props = defineProps<{
    patient: Patient;
    appointments: Appointment[];
    consultations: Consultation[] | null;
    payments: Payment[] | null;
    can: {
        update: boolean;
        fill_preliminary: boolean;
        preliminary_locked: boolean;
        view_medical_history: boolean;
        create_consultation: boolean;
        create_appointment: boolean;
        create_payment: boolean;
        view_audit_trail: boolean;
    };
}>();

const page = usePage();
const isOwnRecord = computed(
    () => page.props.auth.patientId === props.patient.id,
);

const breadcrumbs: BreadcrumbItem[] = isOwnRecord.value
    ? [{ title: 'Mi historial', href: patientRoutes.show(props.patient.id) }]
    : [
          { title: 'Pacientes', href: patientRoutes.index() },
          {
              title: props.patient.full_name,
              href: patientRoutes.show(props.patient.id),
          },
      ];

const details: { label: string; value: string | null }[] = [
    { label: 'Documento', value: props.patient.document_number },
    {
        label: 'Nacimiento',
        value: props.patient.birth_date
            ? `${formatDate(props.patient.birth_date)} (${props.patient.age} años)`
            : null,
    },
    { label: 'Sexo', value: props.patient.gender?.label ?? null },
    { label: 'Grupo sanguíneo', value: props.patient.blood_type },
    { label: 'Correo', value: props.patient.email },
    { label: 'Teléfono', value: props.patient.phone },
    { label: 'Dirección', value: props.patient.address },
    {
        label: 'Emergencia',
        value: props.patient.emergency_contact_name
            ? `${props.patient.emergency_contact_name} · ${props.patient.emergency_contact_phone ?? ''}`
            : null,
    },
];
</script>

<template>
    <Head :title="patient.full_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ patient.full_name }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Paciente desde {{ formatDate(patient.created_at) }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-if="can.view_audit_trail"
                        variant="outline"
                        as-child
                    >
                        <Link
                            :href="
                                auditLogRoutes.index({
                                    query: { patient_id: patient.id },
                                })
                            "
                        >
                            <ShieldCheck /> Auditoría
                        </Link>
                    </Button>
                    <Button v-if="can.update" variant="outline" as-child>
                        <Link :href="patientRoutes.edit(patient.id)">
                            <Pencil />
                            {{
                                isOwnRecord ? 'Actualizar mis datos' : 'Editar'
                            }}
                        </Link>
                    </Button>
                    <Button
                        v-if="can.create_payment"
                        variant="outline"
                        as-child
                    >
                        <Link
                            :href="
                                paymentRoutes.create({
                                    query: { patient_id: patient.id },
                                })
                            "
                        >
                            <Wallet /> Registrar pago
                        </Link>
                    </Button>
                    <Button
                        v-if="can.create_appointment"
                        variant="outline"
                        as-child
                    >
                        <Link
                            :href="
                                appointmentRoutes.create({
                                    query: { patient_id: patient.id },
                                })
                            "
                        >
                            <CalendarPlus /> Agendar cita
                        </Link>
                    </Button>
                    <Button v-if="can.create_consultation" as-child>
                        <Link :href="consultationRoutes.create(patient.id)">
                            <FilePlus2 /> Nueva consulta
                        </Link>
                    </Button>
                </div>
            </div>

            <div
                v-if="can.fill_preliminary"
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-sky-300 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-200"
            >
                <p>
                    Completa tus datos básicos (documento, fecha de nacimiento,
                    sexo, teléfono y contacto de emergencia) antes de tu primera
                    consulta. Después de que el médico te atienda ya no podrás
                    editar los datos básicos.
                </p>
                <Button size="sm" as-child>
                    <Link :href="patientRoutes.edit(patient.id)">
                        <Pencil /> Completar ahora
                    </Link>
                </Button>
            </div>
            <p
                v-else-if="can.preliminary_locked"
                class="rounded-lg border bg-muted/40 p-3 text-sm text-muted-foreground"
            >
                Tus datos básicos quedaron registrados en tu primera consulta.
                Si necesitan cambiar, coméntalo con tu médico. Tus datos de
                contacto los puedes actualizar siempre.
            </p>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Datos del paciente</CardTitle>
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

                <Card v-if="can.view_medical_history" class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Antecedentes médicos</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-4 text-sm sm:grid-cols-3">
                        <div>
                            <p
                                class="mb-1 flex items-center gap-1 font-medium text-red-600 dark:text-red-400"
                            >
                                <TriangleAlert class="size-4" /> Alergias
                            </p>
                            <p
                                class="whitespace-pre-line text-muted-foreground"
                            >
                                {{ patient.allergies || 'Ninguna registrada' }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-1 font-medium">
                                Enfermedades crónicas
                            </p>
                            <p
                                class="whitespace-pre-line text-muted-foreground"
                            >
                                {{
                                    patient.chronic_conditions ||
                                    'Ninguna registrada'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-1 font-medium">Otros antecedentes</p>
                            <p
                                class="whitespace-pre-line text-muted-foreground"
                            >
                                {{
                                    patient.medical_background ||
                                    'Sin información'
                                }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="props.consultations">
                <CardHeader>
                    <CardTitle>Historia clínica</CardTitle>
                    <CardDescription>
                        Consultas registradas, de la más reciente a la más
                        antigua
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="props.consultations.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        Aún no hay consultas registradas.
                    </p>
                    <ol v-else class="relative space-y-4 border-l pl-6">
                        <li
                            v-for="consultation in props.consultations"
                            :key="consultation.id"
                            class="relative"
                        >
                            <span
                                class="absolute top-1.5 -left-[29px] size-2.5 rounded-full bg-teal-600"
                            />
                            <Link
                                :href="consultationRoutes.show(consultation.id)"
                                class="block rounded-lg border p-4 transition-colors hover:bg-accent"
                            >
                                <div
                                    class="flex flex-wrap items-baseline justify-between gap-2"
                                >
                                    <p class="font-medium">
                                        {{ consultation.diagnosis }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            formatDateTime(
                                                consultation.consulted_at,
                                            )
                                        }}
                                    </p>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ consultation.doctor?.name }} ·
                                    {{ consultation.doctor?.specialty }}
                                </p>
                                <p class="mt-2 line-clamp-2 text-sm">
                                    Motivo: {{ consultation.reason }}
                                </p>
                                <p
                                    v-if="consultation.prescriptions?.length"
                                    class="mt-2 text-xs text-muted-foreground"
                                >
                                    {{ consultation.prescriptions.length }}
                                    medicamento(s) recetado(s)
                                </p>
                            </Link>
                        </li>
                    </ol>
                </CardContent>
            </Card>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Citas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="props.appointments.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            Sin citas registradas.
                        </p>
                        <ul v-else class="divide-y">
                            <li
                                v-for="appointment in props.appointments"
                                :key="appointment.id"
                                class="flex flex-wrap items-center justify-between gap-2 py-3"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">
                                        {{
                                            formatDateTime(
                                                appointment.scheduled_at,
                                            )
                                        }}
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ appointment.doctor?.name }} ·
                                        {{ appointment.reason }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <IconButton
                                        v-if="
                                            can.create_consultation &&
                                            ['requested', 'confirmed'].includes(
                                                appointment.status.value,
                                            ) &&
                                            !appointment.consultation_id
                                        "
                                        label="Atender"
                                        tone="success"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                consultationRoutes.create(
                                                    patient.id,
                                                    {
                                                        query: {
                                                            appointment_id:
                                                                appointment.id,
                                                        },
                                                    },
                                                )
                                            "
                                        >
                                            <ClipboardList />
                                        </Link>
                                    </IconButton>
                                    <StatusBadge
                                        v-if="appointment.payment_status"
                                        :status="appointment.payment_status"
                                    />
                                    <StatusBadge :status="appointment.status" />
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card v-if="props.payments">
                    <CardHeader>
                        <CardTitle>Pagos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="props.payments.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            Sin pagos registrados.
                        </p>
                        <ul v-else class="divide-y">
                            <li
                                v-for="payment in props.payments"
                                :key="payment.id"
                                class="flex flex-wrap items-center justify-between gap-2 py-3"
                            >
                                <div>
                                    <p class="text-sm font-medium">
                                        {{ payment.concept }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(payment.paid_at) }} ·
                                        {{ payment.method.label }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-medium tabular-nums">{{
                                        formatMoney(payment.amount)
                                    }}</span>
                                    <StatusBadge :status="payment.status" />
                                    <IconButton
                                        label="Ver detalle"
                                        tone="info"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                paymentRoutes.show(payment.id)
                                            "
                                        >
                                            <Eye />
                                        </Link>
                                    </IconButton>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
