<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus, Trash2, TriangleAlert } from 'lucide-vue-next';
import { ref } from 'vue';
import ConsultationController from '@/actions/App/Http/Controllers/ConsultationController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/format';
import consultationRoutes from '@/routes/consultations';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    patient: {
        id: number;
        full_name: string;
        allergies: string | null;
        chronic_conditions: string | null;
    };
    appointments: { id: number; scheduled_at: string; reason: string }[];
    selectedAppointmentId: number | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pacientes', href: patientRoutes.index() },
    {
        title: props.patient.full_name,
        href: patientRoutes.show(props.patient.id),
    },
    {
        title: 'Nueva consulta',
        href: consultationRoutes.create(props.patient.id),
    },
];

let nextPrescriptionKey = 0;
const prescriptionRows = ref<number[]>([]);

function addPrescription() {
    prescriptionRows.value.push(nextPrescriptionKey++);
}

function removePrescription(key: number) {
    prescriptionRows.value = prescriptionRows.value.filter(
        (row) => row !== key,
    );
}

const vitals = [
    { name: 'weight_kg', label: 'Peso (kg)', step: '0.1', type: 'number' },
    { name: 'height_cm', label: 'Talla (cm)', step: '0.1', type: 'number' },
    {
        name: 'blood_pressure',
        label: 'Presión arterial',
        placeholder: '120/80',
        type: 'text',
    },
    {
        name: 'temperature_c',
        label: 'Temperatura (°C)',
        step: '0.1',
        type: 'number',
    },
    {
        name: 'heart_rate',
        label: 'Frecuencia cardíaca',
        step: '1',
        type: 'number',
    },
];
</script>

<template>
    <Head title="Nueva consulta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Nueva consulta
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ patient.full_name }}
                </p>
            </div>

            <div
                v-if="patient.allergies || patient.chronic_conditions"
                class="flex gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
            >
                <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                <div>
                    <p v-if="patient.allergies">
                        <strong>Alergias:</strong> {{ patient.allergies }}
                    </p>
                    <p v-if="patient.chronic_conditions">
                        <strong>Condiciones crónicas:</strong>
                        {{ patient.chronic_conditions }}
                    </p>
                </div>
            </div>

            <Form
                v-bind="ConsultationController.store.form(patient.id)"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <div v-if="appointments.length" class="grid gap-2">
                    <Label for="appointment_id">Cita asociada</Label>
                    <NativeSelect
                        id="appointment_id"
                        name="appointment_id"
                        :default-value="selectedAppointmentId ?? ''"
                    >
                        <option value="">Consulta sin cita previa</option>
                        <option
                            v-for="appointment in appointments"
                            :key="appointment.id"
                            :value="appointment.id"
                        >
                            {{ formatDateTime(appointment.scheduled_at) }} ·
                            {{ appointment.reason }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.appointment_id" />
                </div>

                <section class="space-y-4">
                    <h2
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Signos vitales
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
                        <div
                            v-for="vital in vitals"
                            :key="vital.name"
                            class="grid gap-2"
                        >
                            <Label :for="vital.name">{{ vital.label }}</Label>
                            <Input
                                :id="vital.name"
                                :name="vital.name"
                                :type="vital.type"
                                :step="vital.step"
                                :placeholder="vital.placeholder"
                            />
                            <InputError :message="errors[vital.name]" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <h2
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Evaluación
                    </h2>
                    <div class="grid gap-2">
                        <Label for="reason">Motivo de consulta *</Label>
                        <Textarea id="reason" name="reason" required />
                        <InputError :message="errors.reason" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="symptoms">Síntomas y examen físico</Label>
                        <Textarea id="symptoms" name="symptoms" />
                        <InputError :message="errors.symptoms" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="diagnosis">Diagnóstico *</Label>
                        <Textarea id="diagnosis" name="diagnosis" required />
                        <InputError :message="errors.diagnosis" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="treatment"
                            >Tratamiento / indicaciones</Label
                        >
                        <Textarea id="treatment" name="treatment" />
                        <InputError :message="errors.treatment" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="notes">Notas internas</Label>
                        <Textarea id="notes" name="notes" />
                        <InputError :message="errors.notes" />
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2
                            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Receta
                        </h2>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="addPrescription"
                        >
                            <Plus /> Agregar medicamento
                        </Button>
                    </div>
                    <p
                        v-if="prescriptionRows.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Sin medicamentos recetados.
                    </p>
                    <div
                        v-for="(rowKey, index) in prescriptionRows"
                        :key="rowKey"
                        class="grid gap-3 rounded-lg border p-4 sm:grid-cols-2"
                    >
                        <div class="grid gap-2">
                            <Label :for="`medication-${rowKey}`"
                                >Medicamento *</Label
                            >
                            <Input
                                :id="`medication-${rowKey}`"
                                :name="`prescriptions[${index}][medication]`"
                                required
                            />
                            <InputError
                                :message="
                                    errors[`prescriptions.${index}.medication`]
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`dosage-${rowKey}`">Dosis *</Label>
                            <Input
                                :id="`dosage-${rowKey}`"
                                :name="`prescriptions[${index}][dosage]`"
                                placeholder="500 mg"
                                required
                            />
                            <InputError
                                :message="
                                    errors[`prescriptions.${index}.dosage`]
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`frequency-${rowKey}`"
                                >Frecuencia *</Label
                            >
                            <Input
                                :id="`frequency-${rowKey}`"
                                :name="`prescriptions[${index}][frequency]`"
                                placeholder="Cada 8 horas"
                                required
                            />
                            <InputError
                                :message="
                                    errors[`prescriptions.${index}.frequency`]
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`duration-${rowKey}`">Duración</Label>
                            <Input
                                :id="`duration-${rowKey}`"
                                :name="`prescriptions[${index}][duration]`"
                                placeholder="7 días"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label :for="`instructions-${rowKey}`"
                                >Instrucciones</Label
                            >
                            <Input
                                :id="`instructions-${rowKey}`"
                                :name="`prescriptions[${index}][instructions]`"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                class="text-destructive"
                                @click="removePrescription(rowKey)"
                            >
                                <Trash2 /> Quitar
                            </Button>
                        </div>
                    </div>
                </section>

                <div class="flex gap-2">
                    <Button :disabled="processing">Guardar consulta</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="patientRoutes.show(patient.id)"
                            >Cancelar</Link
                        >
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
