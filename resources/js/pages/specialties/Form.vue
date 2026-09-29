<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import SpecialtyController from '@/actions/App/Http/Controllers/SpecialtyController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import specialtyRoutes from '@/routes/specialties';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    specialty: { id: number; name: string; description: string | null } | null;
}>();

const title = computed(() =>
    props.specialty ? 'Editar especialidad' : 'Nueva especialidad',
);

const formAction = computed(() =>
    props.specialty
        ? SpecialtyController.update.form(props.specialty.id)
        : SpecialtyController.store.form(),
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Especialidades', href: specialtyRoutes.index() },
    {
        title: props.specialty ? 'Editar' : 'Nueva',
        href: props.specialty
            ? specialtyRoutes.edit(props.specialty.id)
            : specialtyRoutes.create(),
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
                        :default-value="specialty?.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="description">Descripción</Label>
                    <Textarea
                        id="description"
                        name="description"
                        :default-value="specialty?.description ?? ''"
                    />
                    <p class="text-xs text-muted-foreground">
                        Se muestra en la página de inicio.
                    </p>
                    <InputError :message="errors.description" />
                </div>
                <div class="flex gap-2">
                    <Button :disabled="processing">Guardar</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="specialtyRoutes.index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
