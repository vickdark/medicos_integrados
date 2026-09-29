<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import paymentRoutes from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';
import type { Option } from '@/types/models';

const props = defineProps<{
    patients: {
        id: number;
        full_name: string;
        document_number: string | null;
    }[];
    selectedPatientId: number | null;
    appointments: { id: number; label: string }[];
    methods: Option[];
    statuses: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pagos', href: paymentRoutes.index() },
    { title: 'Registrar pago', href: paymentRoutes.create() },
];

const status = ref('paid');
const today = new Date().toISOString().slice(0, 10);

function loadPatientAppointments(event: Event) {
    const patientId = (event.target as HTMLSelectElement).value;

    router.reload({
        data: { patient_id: patientId },
        only: ['appointments', 'selectedPatientId'],
    });
}
</script>

<template>
    <Head title="Registrar pago" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
            <h1 class="text-2xl font-semibold tracking-tight">
                Registrar pago
            </h1>

            <Form
                v-bind="PaymentController.store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="patient_id">Paciente *</Label>
                    <NativeSelect
                        id="patient_id"
                        name="patient_id"
                        :default-value="props.selectedPatientId ?? ''"
                        required
                        @change="loadPatientAppointments"
                    >
                        <option value="" disabled>
                            Selecciona un paciente
                        </option>
                        <option
                            v-for="patient in props.patients"
                            :key="patient.id"
                            :value="patient.id"
                        >
                            {{ patient.full_name
                            }}{{
                                patient.document_number
                                    ? ` · ${patient.document_number}`
                                    : ''
                            }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.patient_id" />
                </div>

                <div v-if="props.appointments.length" class="grid gap-2">
                    <Label for="appointment_id">Cita relacionada</Label>
                    <NativeSelect
                        id="appointment_id"
                        name="appointment_id"
                        default-value=""
                    >
                        <option value="">Ninguna</option>
                        <option
                            v-for="appointment in props.appointments"
                            :key="appointment.id"
                            :value="appointment.id"
                        >
                            {{ appointment.label }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.appointment_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="concept">Concepto *</Label>
                    <Input
                        id="concept"
                        name="concept"
                        placeholder="Consulta médica"
                        required
                    />
                    <InputError :message="errors.concept" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="amount">Monto *</Label>
                        <Input
                            id="amount"
                            type="number"
                            name="amount"
                            step="0.01"
                            min="0.01"
                            required
                        />
                        <InputError :message="errors.amount" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="method">Método de pago *</Label>
                        <NativeSelect
                            id="method"
                            name="method"
                            default-value="cash"
                            required
                        >
                            <option
                                v-for="method in props.methods"
                                :key="method.value"
                                :value="method.value"
                            >
                                {{ method.label }}
                            </option>
                        </NativeSelect>
                        <InputError :message="errors.method" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="status">Estado *</Label>
                        <NativeSelect
                            id="status"
                            v-model="status"
                            name="status"
                            required
                        >
                            <option
                                v-for="option in props.statuses"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </NativeSelect>
                        <InputError :message="errors.status" />
                    </div>
                    <div v-if="status === 'paid'" class="grid gap-2">
                        <Label for="paid_at">Fecha de pago *</Label>
                        <Input
                            id="paid_at"
                            type="date"
                            name="paid_at"
                            :default-value="today"
                            :max="today"
                            required
                        />
                        <InputError :message="errors.paid_at" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="reference"
                        >Referencia / N.º de comprobante</Label
                    >
                    <Input id="reference" name="reference" />
                    <InputError :message="errors.reference" />
                </div>

                <div class="grid gap-2">
                    <Label for="notes">Notas</Label>
                    <Textarea id="notes" name="notes" />
                    <InputError :message="errors.notes" />
                </div>

                <div class="flex gap-2">
                    <Button :disabled="processing">Registrar pago</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="paymentRoutes.index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
