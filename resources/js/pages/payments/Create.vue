<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import InputError from '@/components/InputError.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
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
    prefill: {
        appointment_id: number | null;
        concept: string;
        amount: string;
        method: string | null;
    } | null;
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

const patientOptions = computed(() =>
    props.patients.map((patient) => ({
        value: patient.id,
        label: patient.document_number
            ? `${patient.full_name} · ${patient.document_number}`
            : patient.full_name,
    })),
);

function loadPatientAppointments(patientId: string | number) {
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
                    <SearchableSelect
                        id="patient_id"
                        name="patient_id"
                        :options="patientOptions"
                        :default-value="props.selectedPatientId ?? ''"
                        placeholder="Selecciona un paciente"
                        search-placeholder="Buscar por nombre o documento"
                        required
                        @change="loadPatientAppointments"
                    />
                    <InputError :message="errors.patient_id" />
                </div>

                <div v-if="props.appointments.length" class="grid gap-2">
                    <Label for="appointment_id">Cita relacionada</Label>
                    <NativeSelect
                        id="appointment_id"
                        name="appointment_id"
                        :default-value="props.prefill?.appointment_id ?? ''"
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
                        :default-value="props.prefill?.concept ?? ''"
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
                            :default-value="props.prefill?.amount ?? ''"
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
                            :default-value="props.prefill?.method ?? 'cash'"
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
