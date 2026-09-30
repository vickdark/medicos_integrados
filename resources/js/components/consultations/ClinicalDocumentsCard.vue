<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { FilePlus2, FileText, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ClinicalDocumentController from '@/actions/App/Http/Controllers/ClinicalDocumentController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { formatDateTime } from '@/lib/format';
import clinicalDocumentRoutes from '@/routes/clinical-documents';
import type { ClinicalDocumentSummary, Option } from '@/types/models';

export type DocumentOptions = {
    types: Option[];
    sickLeaveOrigins: Option[];
    priorities: Option[];
    specialties: string[];
    maxSickLeaveDays: number;
};

const props = defineProps<{
    consultationId: number;
    documents: ClinicalDocumentSummary[];
    canIssue: boolean;
    options: DocumentOptions;
    issuedDocumentId: number | null;
}>();

const dialogOpen = ref(false);
const type = ref('');
const today = new Date().toISOString().slice(0, 10);

let nextExamKey = 1;
const examRows = ref<number[]>([0]);

function addExam() {
    examRows.value.push(nextExamKey++);
}

function removeExam(key: number) {
    examRows.value = examRows.value.filter((row) => row !== key);
}

watch(dialogOpen, (open) => {
    if (!open) {
        type.value = '';
        examRows.value = [nextExamKey++];
    }
});

const selectedType = computed(() =>
    props.options.types.find((option) => option.value === type.value),
);

const pdfUrl = (id: number) => clinicalDocumentRoutes.show(id).url;
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="grid gap-1.5">
                    <CardTitle>Documentos clínicos</CardTitle>
                    <CardDescription>
                        Consentimiento informado, incapacidades, remisiones y
                        órdenes de exámenes emitidos en esta consulta.
                    </CardDescription>
                </div>
                <Button
                    v-if="canIssue"
                    size="sm"
                    variant="outline"
                    @click="dialogOpen = true"
                >
                    <FilePlus2 /> Emitir documento
                </Button>
            </div>
        </CardHeader>
        <CardContent>
            <p
                v-if="documents.length === 0"
                class="text-sm text-muted-foreground"
            >
                No se han emitido documentos.
            </p>
            <ul v-else class="divide-y rounded-lg border">
                <li
                    v-for="document in documents"
                    :key="document.id"
                    class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm"
                    :class="{
                        'bg-emerald-50/60 dark:bg-emerald-500/5':
                            document.id === issuedDocumentId,
                    }"
                >
                    <div class="min-w-0">
                        <p class="font-medium">
                            {{ document.type.label }}
                            <span
                                class="ml-1 font-mono text-xs text-muted-foreground"
                                >{{ document.number }}</span
                            >
                            <span
                                v-if="document.id === issuedDocumentId"
                                class="ml-2 rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300"
                                >Nuevo</span
                            >
                        </p>
                        <p class="truncate text-muted-foreground">
                            {{ document.summary }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDateTime(document.created_at) }}
                        </p>
                    </div>
                    <Button size="sm" variant="outline" as-child>
                        <a
                            :href="pdfUrl(document.id)"
                            target="_blank"
                            rel="noopener"
                        >
                            <FileText class="text-red-600" /> Ver PDF
                        </a>
                    </Button>
                </li>
            </ul>
        </CardContent>
    </Card>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="max-h-[90svh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Emitir documento clínico</DialogTitle>
                <DialogDescription>
                    Una vez emitido no se puede modificar. Imprímelo y fírmalo,
                    o entrégalo al paciente.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="ClinicalDocumentController.store.form(consultationId)"
                class="grid gap-4"
                :options="{ preserveScroll: true }"
                v-slot="{ errors, processing }"
                @success="dialogOpen = false"
            >
                <div class="grid gap-2">
                    <Label for="document-type">Tipo de documento *</Label>
                    <NativeSelect
                        id="document-type"
                        v-model="type"
                        name="type"
                        required
                    >
                        <option value="" disabled>Selecciona</option>
                        <option
                            v-for="option in options.types"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.type" />
                </div>

                <template v-if="type === 'consent'">
                    <div class="grid gap-2">
                        <Label for="consent-procedure">Procedimiento *</Label>
                        <Input
                            id="consent-procedure"
                            name="procedure"
                            placeholder="Ej.: Infiltración de rodilla derecha"
                            required
                        />
                        <InputError :message="errors.procedure" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="consent-description"
                            >En qué consiste *</Label
                        >
                        <Textarea
                            id="consent-description"
                            name="description"
                            rows="3"
                            required
                        />
                        <InputError :message="errors.description" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="consent-risks">Riesgos *</Label>
                        <Textarea
                            id="consent-risks"
                            name="risks"
                            rows="3"
                            required
                        />
                        <InputError :message="errors.risks" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="consent-benefits">Beneficios</Label>
                            <Textarea
                                id="consent-benefits"
                                name="benefits"
                                rows="2"
                            />
                            <InputError :message="errors.benefits" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="consent-alternatives"
                                >Alternativas</Label
                            >
                            <Textarea
                                id="consent-alternatives"
                                name="alternatives"
                                rows="2"
                            />
                            <InputError :message="errors.alternatives" />
                        </div>
                    </div>
                </template>

                <template v-else-if="type === 'sick_leave'">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="leave-start">Fecha de inicio *</Label>
                            <Input
                                id="leave-start"
                                type="date"
                                name="start_date"
                                :default-value="today"
                                required
                            />
                            <InputError :message="errors.start_date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="leave-days">Días *</Label>
                            <Input
                                id="leave-days"
                                type="number"
                                name="days"
                                min="1"
                                :max="options.maxSickLeaveDays"
                                required
                            />
                            <InputError :message="errors.days" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="leave-origin">Origen *</Label>
                        <NativeSelect
                            id="leave-origin"
                            name="origin"
                            default-value="general_illness"
                            required
                        >
                            <option
                                v-for="origin in options.sickLeaveOrigins"
                                :key="origin.value"
                                :value="origin.value"
                            >
                                {{ origin.label }}
                            </option>
                        </NativeSelect>
                        <InputError :message="errors.origin" />
                    </div>
                    <Label
                        for="leave-extension"
                        class="flex items-center gap-2 font-normal"
                    >
                        <input
                            id="leave-extension"
                            type="checkbox"
                            name="is_extension"
                            value="1"
                            class="size-4 accent-primary"
                        />
                        Es una prórroga de una incapacidad anterior
                    </Label>
                    <div class="grid gap-2">
                        <Label for="leave-notes">Observaciones</Label>
                        <Textarea id="leave-notes" name="notes" rows="2" />
                        <InputError :message="errors.notes" />
                    </div>
                </template>

                <template v-else-if="type === 'referral'">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="referral-specialty"
                                >Especialidad o servicio *</Label
                            >
                            <Input
                                id="referral-specialty"
                                name="specialty"
                                list="referral-specialties"
                                placeholder="Ej.: Cardiología"
                                required
                            />
                            <datalist id="referral-specialties">
                                <option
                                    v-for="specialty in options.specialties"
                                    :key="specialty"
                                    :value="specialty"
                                />
                            </datalist>
                            <InputError :message="errors.specialty" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="referral-priority">Prioridad *</Label>
                            <NativeSelect
                                id="referral-priority"
                                name="priority"
                                default-value="routine"
                                required
                            >
                                <option
                                    v-for="priority in options.priorities"
                                    :key="priority.value"
                                    :value="priority.value"
                                >
                                    {{ priority.label }}
                                </option>
                            </NativeSelect>
                            <InputError :message="errors.priority" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="referral-reason"
                            >Motivo de la remisión *</Label
                        >
                        <Textarea
                            id="referral-reason"
                            name="reason"
                            rows="3"
                            required
                        />
                        <InputError :message="errors.reason" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="referral-summary"
                            >Resumen de la historia clínica</Label
                        >
                        <Textarea
                            id="referral-summary"
                            name="clinical_summary"
                            rows="3"
                        />
                        <InputError :message="errors.clinical_summary" />
                    </div>
                </template>

                <template v-else-if="type === 'exam_order'">
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <Label>Exámenes *</Label>
                            <Button
                                v-if="examRows.length < 20"
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="addExam"
                            >
                                <Plus /> Agregar examen
                            </Button>
                        </div>
                        <div
                            v-for="(rowKey, index) in examRows"
                            :key="rowKey"
                            class="flex items-start gap-2"
                        >
                            <div class="grid flex-1 gap-1">
                                <Input
                                    :name="`exams[${index}]`"
                                    :placeholder="
                                        index === 0
                                            ? 'Ej.: Hemograma completo'
                                            : 'Otro examen'
                                    "
                                    :aria-label="`Examen ${index + 1}`"
                                    required
                                />
                                <InputError
                                    :message="errors[`exams.${index}`]"
                                />
                            </div>
                            <Button
                                v-if="examRows.length > 1"
                                type="button"
                                size="icon"
                                variant="ghost"
                                class="text-destructive"
                                :aria-label="`Quitar examen ${index + 1}`"
                                @click="removeExam(rowKey)"
                            >
                                <Trash2 />
                            </Button>
                        </div>
                        <InputError :message="errors.exams" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="exam-priority">Prioridad *</Label>
                        <NativeSelect
                            id="exam-priority"
                            name="priority"
                            default-value="routine"
                            required
                        >
                            <option
                                v-for="priority in options.priorities"
                                :key="priority.value"
                                :value="priority.value"
                            >
                                {{ priority.label }}
                            </option>
                        </NativeSelect>
                        <InputError :message="errors.priority" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="exam-indications">Indicaciones</Label>
                        <Textarea
                            id="exam-indications"
                            name="indications"
                            rows="2"
                            placeholder="Ej.: ayuno de 8 horas"
                        />
                        <InputError :message="errors.indications" />
                    </div>
                </template>

                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="dialogOpen = false"
                    >
                        Cancelar
                    </Button>
                    <Button :disabled="processing || !selectedType">
                        <FilePlus2 />
                        {{
                            selectedType
                                ? `Emitir ${selectedType.label.toLowerCase()}`
                                : 'Emitir'
                        }}
                    </Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
