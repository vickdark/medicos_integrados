<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { Download, FileImage, FileText, Trash2, Upload } from 'lucide-vue-next';
import { computed } from 'vue';
import ConsultationAttachmentController from '@/actions/App/Http/Controllers/ConsultationAttachmentController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime, formatFileSize } from '@/lib/format';
import attachmentRoutes from '@/routes/attachments';
import consultationRoutes from '@/routes/consultations';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type { Attachment, Consultation } from '@/types/models';

const props = defineProps<{
    consultation: Consultation;
    can: { manage_attachments: boolean };
}>();

function destroyAttachment(attachment: Attachment) {
    if (!confirm(`¿Eliminar el archivo «${attachment.original_name}»?`)) {
        return;
    }

    router.delete(attachmentRoutes.destroy(attachment.id).url, {
        preserveScroll: true,
    });
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
                            <Button
                                size="icon"
                                variant="ghost"
                                as="a"
                                :href="attachmentRoutes.show(attachment.id).url"
                                aria-label="Descargar"
                            >
                                <Download />
                            </Button>
                            <Button
                                v-if="can.manage_attachments"
                                size="icon"
                                variant="ghost"
                                class="text-destructive"
                                aria-label="Eliminar archivo"
                                @click="destroyAttachment(attachment)"
                            >
                                <Trash2 />
                            </Button>
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
    </AppLayout>
</template>
