<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Download,
    FileImage,
    FileText,
    Mail,
    Trash2,
    TriangleAlert,
    Upload,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
        download_prescription: boolean;
        email_prescription: boolean;
    };
    patientEmail: string | null;
}>();

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
