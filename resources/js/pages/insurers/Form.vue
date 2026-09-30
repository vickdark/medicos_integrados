<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import InsurerController from '@/actions/App/Http/Controllers/InsurerController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import insurerRoutes from '@/routes/insurers';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    insurer: { id: number; name: string; code: string | null } | null;
}>();

const title = computed(() =>
    props.insurer ? 'Editar aseguradora' : 'Nueva aseguradora',
);

const formAction = computed(() =>
    props.insurer
        ? InsurerController.update.form(props.insurer.id)
        : InsurerController.store.form(),
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Aseguradoras (EPS)', href: insurerRoutes.index() },
    {
        title: props.insurer ? 'Editar' : 'Nueva',
        href: props.insurer
            ? insurerRoutes.edit(props.insurer.id)
            : insurerRoutes.create(),
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
                        :default-value="insurer?.name"
                        placeholder="Nueva EPS"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="code">Código de la entidad</Label>
                    <Input
                        id="code"
                        name="code"
                        :default-value="insurer?.code ?? ''"
                        placeholder="EPS037"
                    />
                    <p class="text-xs text-muted-foreground">
                        Código asignado por la Superintendencia Nacional de
                        Salud. Se usará al reportar los RIPS.
                    </p>
                    <InputError :message="errors.code" />
                </div>
                <div class="flex gap-2">
                    <Button :disabled="processing">Guardar</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="insurerRoutes.index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
