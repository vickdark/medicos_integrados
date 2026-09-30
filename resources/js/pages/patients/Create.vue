<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PatientController from '@/actions/App/Http/Controllers/PatientController';
import PatientFormFields from '@/components/patients/PatientFormFields.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type { Option } from '@/types/models';

defineProps<{
    genders: Option[];
    bloodTypes: string[];
    documentTypes: Option[];
    affiliationTypes: Option[];
    insurers: { value: number; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pacientes', href: patientRoutes.index() },
    { title: 'Nuevo paciente', href: patientRoutes.create() },
];
</script>

<template>
    <Head title="Nuevo paciente" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
            <h1 class="text-2xl font-semibold tracking-tight">
                Nuevo paciente
            </h1>

            <Form
                v-bind="PatientController.store.form()"
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
                    show-clinical-fields
                />

                <div class="flex gap-2">
                    <Button :disabled="processing">Registrar paciente</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="patientRoutes.index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
