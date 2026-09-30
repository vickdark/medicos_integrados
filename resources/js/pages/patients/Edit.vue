<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PatientController from '@/actions/App/Http/Controllers/PatientController';
import PatientFormFields from '@/components/patients/PatientFormFields.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type { Option, Patient } from '@/types/models';

const props = defineProps<{
    patient: Patient;
    contactOnly: boolean;
    preliminaryEditable: boolean;
    genders: Option[];
    bloodTypes: string[];
    documentTypes: Option[];
    affiliationTypes: Option[];
    insurers: { value: number; label: string }[];
}>();

const title = props.contactOnly ? 'Actualizar mis datos' : 'Editar paciente';

const breadcrumbs: BreadcrumbItem[] = props.contactOnly
    ? [
          { title: 'Mi historial', href: patientRoutes.show(props.patient.id) },
          { title, href: patientRoutes.edit(props.patient.id) },
      ]
    : [
          { title: 'Pacientes', href: patientRoutes.index() },
          {
              title: props.patient.full_name,
              href: patientRoutes.show(props.patient.id),
          },
          { title: 'Editar', href: patientRoutes.edit(props.patient.id) },
      ];
</script>

<template>
    <Head :title="contactOnly ? title : `Editar · ${patient.full_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <p v-if="contactOnly" class="text-sm text-muted-foreground">
                    {{
                        preliminaryEditable
                            ? 'Necesitamos algunos datos básicos para tu ficha. Tus antecedentes médicos los registra tu médico en consulta.'
                            : 'Para corregir tu nombre, documento o datos médicos, comunícate con la clínica.'
                    }}
                </p>
            </div>

            <Form
                v-bind="PatientController.update.form(patient.id)"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <PatientFormFields
                    :errors="errors"
                    :genders="genders"
                    :blood-types="bloodTypes"
                    :document-types="documentTypes"
                    :affiliation-types="affiliationTypes"
                    :insurers="insurers"
                    :patient="patient"
                    :show-clinical-fields="patient.allergies !== undefined"
                    :contact-only="contactOnly"
                    :preliminary="contactOnly && preliminaryEditable"
                />

                <div class="flex gap-2">
                    <Button :disabled="processing">Guardar cambios</Button>
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
