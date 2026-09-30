<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Download,
    FileImage,
    FilePenLine,
    FileText,
    Mail,
    Trash2,
    TriangleAlert,
    Upload,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConsultationAddendumController from '@/actions/App/Http/Controllers/ConsultationAddendumController';
import ConsultationAttachmentController from '@/actions/App/Http/Controllers/ConsultationAttachmentController';
import SendConsultationPrescriptionController from '@/actions/App/Http/Controllers/SendConsultationPrescriptionController';
import IconButton from '@/components/IconButton.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction } from '@/lib/confirm';
import { formatDateTime, formatFileSize } from '@/lib/format';
import attachmentRoutes from '@/routes/attachments';
import consultationRoutes from '@/routes/consultations';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type { Attachment, Consultation } from '@/types/models';

const props = defineProps<{
    consultation: Consultation;
    can: {
        manage_attachments: boolean;
        add_addendum: boolean;
        download_prescription: boolean;
        email_prescription: boolean;
    };
    patientEmail: string | null;
    addendumSections: { value: string; label: string }[];
}>();

const addenda = computed(() => props.consultation.addenda ?? []);
const addendumFormOpen = ref(false);

const emailDialogOpen = ref(false);

async function destroyAttachment(attachment: Attachment) {
    const accepted = await confirmAction({
        title: 'Eliminar archivo',
        text: `Se eliminará «${attachment.original_name}». Esta acción no se puede deshacer.`,
        confirmText: 'Sí, eliminar',
        cancelText: 'Volver',
        tone: 'danger',
    });

    if (accepted) {
        router.delete(attachmentRoutes.destroy(attachment.id).url, {
            preserveScroll: true,
        });
    }
}

const page = usePage();
const isPatient = computed(() => page.props.auth.role?.value === 'patient');

const breadcrumbs: BreadcrumbItem[] = [
    ...(isPatient.value
        ? []
        : [{ title: 'Pacientes', href: patientRoutes.index() }]),
    {
        title: isPatient.value
            ? 'Mi historial'
            : props.consultation.patient!.full_name,
        href: patientRoutes.show(props.consultation.patient!.id),
    },
    { title: 'Consulta', href: consultationRoutes.show(props.consultation.id) },
];

const vitals = computed(() =>
    [
        { label: 'Peso', value: props.consultation.weight_kg, unit: 'kg' },
        { label: 'Talla', value: props.consultation.height_cm, unit: 'cm' },
        {
            label: 'Presión',
            value: props.consultation.blood_pressure,
            unit: 'mmHg',
        },
        {
            label: 'Temperatura',
            value: props.consultation.temperature_c,
            unit: '°C',
        },
        {
            label: 'Frec. cardíaca',
            value: props.consultation.heart_rate,
            unit: 'lpm',
        },
    ].filter((vital) => vital.value !== null),
);

const sections = computed(() =>
    [
        { title: 'Motivo de consulta', body: props.consultation.reason },
        {
            title: 'Síntomas y examen físico',
            body: props.consultation.symptoms,
        },
        { title: 'Diagnóstico', body: props.consultation.diagnosis },
        {
            title: 'Tratamiento / indicaciones',
            body: props.consultation.treatment,
        },
        { title: 'Notas internas', body: props.consultation.notes },
    ].filter((section) => section.body),
);
</script>

<template>
    <Head title="Consulta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Consulta del {{ formatDateTime(consultation.consulted_at) }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    <Link
                        :href="patientRoutes.show(consultation.patient!.id)"
                        class="hover:underline"
                    >
                        {{ consultation.patient?.full_name }}
                    </Link>
                    · Atendido por {{ consultation.doctor?.name }} ({{
                        consultation.doctor?.specialty
                    }})
                </p>
            </div>

            <a
                v-if="addenda.length"
                href="#notas-aclaratorias"
                class="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 hover:bg-amber-100 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/15"
            >
                <FilePenLine class="mt-0.5 size-4 shrink-0" />
                <span>
                    Esta consulta tiene
                    <strong>
                        {{ addenda.length }}
                        {{
                            addenda.length === 1
                                ? 'nota aclaratoria'
                                : 'notas aclaratorias'
                        }}</strong
                    >. El registro original se muestra tal como se escribió.
                </span>
            </a>

            <div
                v-if="vitals.length"
                class="grid grid-cols-2 gap-3 sm:grid-cols-5"
            >
                <div
                    v-for="vital in vitals"
                    :key="vital.label"
                    class="rounded-lg border p-3"
                >
                    <p class="text-xs text-muted-foreground">
                        {{ vital.label }}
                    </p>
                    <p class="text-lg font-semibold tabular-nums">
                        {{ vital.value }}
                        <span
                            class="text-xs font-normal text-muted-foreground"
                            >{{ vital.unit }}</span
                        >
                    </p>
                </div>
            </div>

            <Card>
                <CardContent class="space-y-5">
                    <div v-if="consultation.primary_diagnosis">
                        <h2 class="mb-2 text-sm font-semibold">
                            Diagnósticos CIE-10
                        </h2>
                        <ul class="grid gap-1.5 text-sm">
                            <li class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded bg-brand-50 px-1.5 py-0.5 font-mono text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300"
                                >
                                    {{ consultation.primary_diagnosis.code }}
                                </span>
                                <span>{{
                                    consultation.primary_diagnosis.description
                                }}</span>
                                <span
                                    v-if="consultation.diagnosis_type"
                                    class="text-xs text-muted-foreground"
                                >
                                    · Principal ·
                                    {{ consultation.diagnosis_type.label }}
                                </span>
                            </li>
                            <li
                                v-for="related in consultation.related_diagnoses ??
                                []"
                                :key="related.id"
                                class="flex flex-wrap items-center gap-2 text-muted-foreground"
                            >
                                <span
                                    class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs font-semibold"
                                >
                                    {{ related.code }}
                                </span>
                                <span>{{ related.description }}</span>
                                <span class="text-xs">· Relacionado</span>
                            </li>
                        </ul>
                    </div>
                    <div v-for="section in sections" :key="section.title">
                        <h2 class="mb-1 text-sm font-semibold">
                            {{ section.title }}
                        </h2>
                        <p
                            class="text-sm whitespace-pre-line text-muted-foreground"
                        >
                            {{ section.body }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card id="notas-aclaratorias" class="scroll-mt-20">
                <CardHeader>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="grid gap-1.5">
                            <CardTitle>Notas aclaratorias</CardTitle>
                            <CardDescription>
                                La consulta no se edita ni se borra: las
                                correcciones se agregan aquí con su fecha, autor
                                y motivo.
                            </CardDescription>
                        </div>
                        <Button
                            v-if="can.add_addendum && !addendumFormOpen"
                            size="sm"
                            variant="outline"
                            @click="addendumFormOpen = true"
                        >
                            <FilePenLine /> Agregar nota aclaratoria
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <p
                        v-if="addenda.length === 0 && !addendumFormOpen"
                        class="text-sm text-muted-foreground"
                    >
                        Sin notas aclaratorias.
                    </p>

                    <ol v-if="addenda.length" class="grid gap-3">
                        <li
                            v-for="addendum in addenda"
                            :key="addendum.id"
                            class="rounded-lg border-l-4 border-amber-400 bg-amber-50/60 p-3 text-sm dark:border-amber-500/60 dark:bg-amber-500/5"
                        >
                            <p
                                class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground"
                            >
                                <span
                                    class="rounded bg-amber-100 px-1.5 py-0.5 font-medium text-amber-900 dark:bg-amber-500/15 dark:text-amber-200"
                                >
                                    {{ addendum.section.label }}
                                </span>
                                <span>{{ formatDateTime(addendum.created_at) }}</span>
                                <span>· {{ addendum.author }}</span>
                            </p>
                            <p class="mt-2">
                                <span class="font-medium">Motivo:</span>
                                {{ addendum.reason }}
                            </p>
                            <p class="mt-1 whitespace-pre-line">
                                {{ addendum.content }}
                            </p>
                        </li>
                    </ol>

                    <Form
                        v-if="can.add_addendum && addendumFormOpen"
                        v-bind="
                            ConsultationAddendumController.store.form(
                                consultation.id,
                            )
                        "
                        class="grid gap-4 rounded-lg border p-4"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        v-slot="{ errors, processing }"
                        @success="addendumFormOpen = false"
                    >
                        <p class="text-xs text-muted-foreground">
                            La nota queda firmada con tu nombre y la fecha
                            actual, y no se podrá modificar ni eliminar.
                        </p>
                        <div class="grid gap-2">
                            <Label for="addendum-section">¿Qué aclaras? *</Label>
                            <NativeSelect
                                id="addendum-section"
                                name="section"
                                default-value=""
                                required
                            >
                                <option value="" disabled>Selecciona</option>
                                <option
                                    v-for="section in addendumSections"
                                    :key="section.value"
                                    :value="section.value"
                                >
                                    {{ section.label }}
                                </option>
                            </NativeSelect>
                            <InputError :message="errors.section" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="addendum-reason">Motivo *</Label>
                            <Input
                                id="addendum-reason"
                                name="reason"
                                maxlength="255"
                                placeholder="Ej.: error de transcripción en la dosis"
                                required
                            />
                            <InputError :message="errors.reason" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="addendum-content">Aclaración *</Label>
                            <Textarea
                                id="addendum-content"
                                name="content"
                                rows="4"
                                placeholder="Escribe el dato correcto o la información que completa el registro"
                                required
                            />
                            <InputError :message="errors.content" />
                        </div>
                        <div class="flex gap-2">
                            <Button :disabled="processing">
                                Guardar nota aclaratoria
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                @click="addendumFormOpen = false"
                            >
                                Cancelar
                            </Button>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Receta</CardTitle>
                    <CardDescription v-if="!consultation.prescriptions?.length">
                        No se recetaron medicamentos.
                    </CardDescription>
                </CardHeader>
                <CardContent v-if="consultation.prescriptions?.length">
                    <div
                        v-if="
                            can.download_prescription && !can.email_prescription
                        "
                        class="mb-3 flex items-start gap-3 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-900 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200"
                    >
                        <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                        <p>
                            <strong>Este documento no es válido.</strong> Es
                            solo una copia de consulta para el administrador del
                            sistema. Para más información, consulta con el
                            médico responsable: {{ consultation.doctor?.name }}.
                        </p>
                    </div>
                    <div
                        v-if="can.download_prescription"
                        class="mb-3 flex flex-wrap justify-end gap-2"
                    >
                        <Button
                            v-if="can.email_prescription"
                            size="sm"
                            variant="outline"
                            @click="emailDialogOpen = true"
                        >
                            <Mail /> Enviar por correo
                        </Button>
                        <Button size="sm" as-child>
                            <a
                                :href="
                                    consultationRoutes.prescription(
                                        consultation.id,
                                    ).url
                                "
                                target="_blank"
                                rel="noopener"
                            >
                                <FileText />
                                {{
                                    can.email_prescription
                                        ? 'Receta en PDF'
                                        : 'Ver receta (solo consulta)'
                                }}
                            </a>
                        </Button>
                    </div>
                    <ul class="divide-y">
                        <li
                            v-for="prescription in consultation.prescriptions"
                            :key="prescription.id"
                            class="py-3"
                        >
                            <p class="font-medium">
                                {{ prescription.medication }} ·
                                {{ prescription.dosage }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ prescription.frequency
                                }}<template v-if="prescription.duration">
                                    durante
                                    {{ prescription.duration }}</template
                                >
                            </p>
                            <p
                                v-if="prescription.instructions"
                                class="text-sm text-muted-foreground"
                            >
                                {{ prescription.instructions }}
                            </p>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Archivos adjuntos</CardTitle>
                    <CardDescription>
                        Resultados de laboratorio, imágenes y otros documentos
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p
                        v-if="!consultation.attachments?.length"
                        class="text-sm text-muted-foreground"
                    >
                        No hay archivos adjuntos.
                    </p>
                    <ul v-else class="divide-y rounded-lg border">
                        <li
                            v-for="attachment in consultation.attachments"
                            :key="attachment.id"
                            class="flex items-center gap-3 px-3 py-2"
                        >
                            <FileImage
                                v-if="attachment.mime_type.startsWith('image/')"
                                class="size-5 shrink-0 text-muted-foreground"
                            />
                            <FileText
                                v-else
                                class="size-5 shrink-0 text-muted-foreground"
                            />
                            <div class="min-w-0 flex-1">
                                <a
                                    :href="
                                        attachmentRoutes.show(attachment.id).url
                                    "
                                    class="block truncate text-sm font-medium hover:underline"
                                >
                                    {{ attachment.original_name }}
                                </a>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatFileSize(attachment.size) }}
                                    <template v-if="attachment.description">
                                        · {{ attachment.description }}
                                    </template>
                                </p>
                            </div>
                            <IconButton
                                label="Descargar"
                                tone="info"
                                as="a"
                                :href="attachmentRoutes.show(attachment.id).url"
                            >
                                <Download />
                            </IconButton>
                            <IconButton
                                v-if="can.manage_attachments"
                                label="Eliminar archivo"
                                tone="danger"
                                @click="destroyAttachment(attachment)"
                            >
                                <Trash2 />
                            </IconButton>
                        </li>
                    </ul>

                    <Form
                        v-if="can.manage_attachments"
                        v-bind="
                            ConsultationAttachmentController.store.form(
                                consultation.id,
                            )
                        "
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        class="grid gap-3 rounded-lg border border-dashed p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                        v-slot="{ errors, processing, progress }"
                    >
                        <div class="grid gap-2">
                            <Label for="file"
                                >Archivo (PDF o imagen, máx. 10 MB)</Label
                            >
                            <Input
                                id="file"
                                type="file"
                                name="file"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                required
                            />
                            <InputError :message="errors.file" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="description">Descripción</Label>
                            <Input
                                id="description"
                                name="description"
                                placeholder="Ej. Hemograma completo"
                            />
                            <InputError :message="errors.description" />
                        </div>
                        <Button :disabled="processing">
                            <Upload />
                            {{ progress ? `${progress.percentage}%` : 'Subir' }}
                        </Button>
                    </Form>
                </CardContent>
            </Card>
        </div>
        <Dialog v-model:open="emailDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Enviar receta por correo</DialogTitle>
                    <DialogDescription>
                        Se enviará el PDF de la receta como adjunto. Usa el
                        correo registrado del paciente o escribe otro si te lo
                        dio en la consulta.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-bind="
                        SendConsultationPrescriptionController.form(
                            consultation.id,
                        )
                    "
                    :options="{ preserveScroll: true }"
                    class="grid gap-4"
                    v-slot="{ errors, processing }"
                    @success="emailDialogOpen = false"
                >
                    <div class="grid gap-2">
                        <Label for="prescription-email">
                            Correo del paciente *
                        </Label>
                        <Input
                            id="prescription-email"
                            type="email"
                            name="email"
                            :default-value="patientEmail ?? ''"
                            placeholder="paciente@correo.com"
                            required
                        />
                        <p
                            v-if="patientEmail"
                            class="text-xs text-muted-foreground"
                        >
                            Correo registrado en el sistema:
                            {{ patientEmail }}
                        </p>
                        <p v-else class="text-xs text-muted-foreground">
                            El paciente no tiene un correo registrado; escribe
                            el que te indique.
                        </p>
                        <InputError :message="errors.email" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            @click="emailDialogOpen = false"
                        >
                            Cancelar
                        </Button>
                        <Button :disabled="processing">
                            <Mail /> Enviar receta
                        </Button>
                    </div>
                </Form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
