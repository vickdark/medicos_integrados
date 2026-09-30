<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus, Trash2, TriangleAlert } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConsultationController from '@/actions/App/Http/Controllers/ConsultationController';
import MedicationController from '@/actions/App/Http/Controllers/MedicationController';
import InputError from '@/components/InputError.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/format';
import consultationRoutes from '@/routes/consultations';
import diagnosisRoutes from '@/routes/diagnoses';
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
    diagnosisTypes: { value: string; label: string }[];
    medications: {
        id: number;
        name: string;
        presentation: string | null;
        concentration: string | null;
        label: string;
    }[];
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

const diagnosisSearchUrl = diagnosisRoutes.search().url;

let nextRelatedKey = 0;
const relatedRows = ref<number[]>([]);

function addRelated() {
    if (relatedRows.value.length < 3) {
        relatedRows.value.push(nextRelatedKey++);
    }
}

function removeRelated(key: number) {
    relatedRows.value = relatedRows.value.filter((row) => row !== key);
}

let nextPrescriptionKey = 0;
const prescriptionRows = ref<{ key: number; medication: string }[]>([]);

function addPrescription() {
    prescriptionRows.value.push({ key: nextPrescriptionKey++, medication: '' });
}

function removePrescription(key: number) {
    prescriptionRows.value = prescriptionRows.value.filter(
        (row) => row.key !== key,
    );
}

const medicationOptions = computed(() =>
    props.medications.map((medication) => ({
        value: medication.label,
        label: medication.label,
    })),
);

const newMedicationRow = ref<number | null>(null);
const medicationDialogOpen = computed({
    get: () => newMedicationRow.value !== null,
    set: (open) => {
        if (!open) {
            newMedicationRow.value = null;
        }
    },
});

function selectCreatedMedication() {
    const row = prescriptionRows.value.find(
        (item) => item.key === newMedicationRow.value,
    );
    const created = props.medications.reduce((newest, medication) =>
        medication.id > newest.id ? medication : newest,
    );

    if (row && created) {
        row.medication = created.label;
    }

    newMedicationRow.value = null;
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
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid content-start gap-2 sm:col-span-2">
                            <Label for="primary_diagnosis_id"
                                >Diagnóstico principal (CIE-10) *</Label
                            >
                            <SearchableSelect
                                id="primary_diagnosis_id"
                                name="primary_diagnosis_id"
                                :options="[]"
                                :search-url="diagnosisSearchUrl"
                                placeholder="Busca por código o descripción"
                                search-placeholder="Ej.: J00, hipertensión, lumbago"
                                required
                            />
                            <InputError
                                :message="errors.primary_diagnosis_id"
                            />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="diagnosis_type"
                                >Tipo de diagnóstico *</Label
                            >
                            <NativeSelect
                                id="diagnosis_type"
                                name="diagnosis_type"
                                default-value=""
                                required
                            >
                                <option value="" disabled>Selecciona</option>
                                <option
                                    v-for="diagnosisType in diagnosisTypes"
                                    :key="diagnosisType.value"
                                    :value="diagnosisType.value"
                                >
                                    {{ diagnosisType.label }}
                                </option>
                            </NativeSelect>
                            <InputError :message="errors.diagnosis_type" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <Label>Diagnósticos relacionados (CIE-10)</Label>
                            <Button
                                v-if="relatedRows.length < 3"
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="addRelated"
                            >
                                <Plus /> Agregar
                            </Button>
                        </div>
                        <p
                            v-if="relatedRows.length === 0"
                            class="text-xs text-muted-foreground"
                        >
                            Opcional, hasta tres.
                        </p>
                        <div
                            v-for="(rowKey, index) in relatedRows"
                            :key="rowKey"
                            class="flex items-start gap-2"
                        >
                            <div class="grid flex-1 gap-1">
                                <SearchableSelect
                                    :id="`related-diagnosis-${rowKey}`"
                                    :name="`related_diagnosis_ids[${index}]`"
                                    :options="[]"
                                    :search-url="diagnosisSearchUrl"
                                    placeholder="Busca por código o descripción"
                                    search-placeholder="Ej.: E11, diabetes"
                                    required
                                />
                                <InputError
                                    :message="
                                        errors[`related_diagnosis_ids.${index}`]
                                    "
                                />
                            </div>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                class="text-destructive"
                                aria-label="Quitar diagnóstico relacionado"
                                @click="removeRelated(rowKey)"
                            >
                                <Trash2 />
                            </Button>
                        </div>
                        <InputError :message="errors.related_diagnosis_ids" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="diagnosis"
                            >Descripción del diagnóstico *</Label
                        >
                        <Textarea
                            id="diagnosis"
                            name="diagnosis"
                            placeholder="Hallazgos y análisis clínico que sustentan el diagnóstico"
                            required
                        />
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
                        v-for="(row, index) in prescriptionRows"
                        :key="row.key"
                        class="grid gap-x-4 gap-y-3 rounded-lg border p-4 sm:grid-cols-3"
                    >
                        <div class="grid content-start gap-2 sm:col-span-3">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <Label :for="`medication-${row.key}`"
                                    >Medicamento *</Label
                                >
                                <button
                                    type="button"
                                    class="text-xs text-primary hover:underline"
                                    @click="newMedicationRow = row.key"
                                >
                                    ¿No está en el catálogo? Crear medicamento
                                </button>
                            </div>
                            <SearchableSelect
                                :id="`medication-${row.key}`"
                                v-model="row.medication"
                                :name="`prescriptions[${index}][medication]`"
                                :options="medicationOptions"
                                placeholder="Elige del catálogo"
                                search-placeholder="Buscar medicamento"
                                required
                            />
                            <InputError
                                :message="
                                    errors[`prescriptions.${index}.medication`]
                                "
                            />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label :for="`dosage-${row.key}`">Dosis *</Label>
                            <Input
                                :id="`dosage-${row.key}`"
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
                        <div class="grid content-start gap-2">
                            <Label :for="`frequency-${row.key}`"
                                >Frecuencia *</Label
                            >
                            <Input
                                :id="`frequency-${row.key}`"
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
                        <div class="grid content-start gap-2">
                            <Label :for="`duration-${row.key}`">Duración</Label>
                            <Input
                                :id="`duration-${row.key}`"
                                :name="`prescriptions[${index}][duration]`"
                                placeholder="7 días"
                            />
                        </div>
                        <div class="grid content-start gap-2 sm:col-span-3">
                            <Label :for="`instructions-${row.key}`"
                                >Instrucciones</Label
                            >
                            <Input
                                :id="`instructions-${row.key}`"
                                :name="`prescriptions[${index}][instructions]`"
                            />
                        </div>
                        <div class="sm:col-span-3">
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                class="text-destructive"
                                @click="removePrescription(row.key)"
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

            <Dialog v-model:open="medicationDialogOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Nuevo medicamento</DialogTitle>
                        <DialogDescription>
                            Se agrega al catálogo y queda seleccionado en la
                            receta.
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        v-bind="MedicationController.store.form()"
                        class="grid gap-4"
                        :options="{ preserveState: true, preserveScroll: true }"
                        v-slot="{ errors, processing }"
                        @success="selectCreatedMedication"
                    >
                        <input type="hidden" name="inline" value="1" />
                        <div class="grid gap-2">
                            <Label for="new-medication-name">Nombre *</Label>
                            <Input
                                id="new-medication-name"
                                name="name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="new-medication-presentation"
                                    >Presentación</Label
                                >
                                <Input
                                    id="new-medication-presentation"
                                    name="presentation"
                                    placeholder="Tabletas"
                                />
                                <InputError :message="errors.presentation" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="new-medication-concentration"
                                    >Concentración</Label
                                >
                                <Input
                                    id="new-medication-concentration"
                                    name="concentration"
                                    placeholder="500 mg"
                                />
                                <InputError :message="errors.concentration" />
                            </div>
                        </div>
                        <Button :disabled="processing">
                            Agregar al catálogo
                        </Button>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
