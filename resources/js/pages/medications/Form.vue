<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import MedicationController from '@/actions/App/Http/Controllers/MedicationController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import medicationRoutes from '@/routes/medications';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    medication: {
        id: number;
        name: string;
        presentation: string | null;
        concentration: string | null;
        description: string | null;
    } | null;
}>();

const title = computed(() =>
    props.medication ? 'Editar medicamento' : 'Nuevo medicamento',
);

const formAction = computed(() =>
    props.medication
        ? MedicationController.update.form(props.medication.id)
        : MedicationController.store.form(),
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Medicamentos', href: medicationRoutes.index() },
    {
        title: props.medication ? 'Editar' : 'Nuevo',
        href: props.medication
            ? medicationRoutes.edit(props.medication.id)
            : medicationRoutes.create(),
    },
];
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-xl flex-1 flex-col gap-6 p-4">
            <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>

            <Form
                v-bind="formAction"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Nombre *</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="medication?.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="presentation">Presentación</Label>
                        <Input
                            id="presentation"
                            name="presentation"
                            placeholder="Tabletas, jarabe, ampolla…"
                            :default-value="medication?.presentation ?? ''"
                        />
                        <InputError :message="errors.presentation" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="concentration">Concentración</Label>
                        <Input
                            id="concentration"
                            name="concentration"
                            placeholder="500 mg"
                            :default-value="medication?.concentration ?? ''"
                        />
                        <InputError :message="errors.concentration" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="description">Descripción</Label>
                    <Textarea
                        id="description"
                        name="description"
                        :default-value="medication?.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>
                <div class="flex gap-2">
                    <Button :disabled="processing">Guardar</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="medicationRoutes.index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
