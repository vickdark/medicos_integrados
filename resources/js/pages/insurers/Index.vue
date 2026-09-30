<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import IconButton from '@/components/IconButton.vue';
import Pagination from '@/components/Pagination.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Button } from '@/components/ui/button';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction } from '@/lib/confirm';
import insurerRoutes from '@/routes/insurers';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, TableFilters } from '@/types/models';

const props = defineProps<{
    insurers: Paginated<{
        id: number;
        name: string;
        code: string | null;
        patients_count: number;
        can_delete: boolean;
    }>;
    filters: TableFilters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Aseguradoras (EPS)', href: insurerRoutes.index() },
];

const { filters, hasActiveFilters, reset } = useTableFilters(
    () => insurerRoutes.index().url,
    { search: props.filters.search },
);

async function destroy(id: number, name: string) {
    const accepted = await confirmAction({
        title: 'Eliminar aseguradora',
        text: `Se eliminará «${name}». Esta acción no se puede deshacer.`,
        confirmText: 'Sí, eliminar',
        cancelText: 'Volver',
        tone: 'danger',
    });

    if (accepted) {
        router.delete(insurerRoutes.destroy(id).url, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Aseguradoras (EPS)" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Aseguradoras (EPS)
                </h1>
                <Button as-child>
                    <Link :href="insurerRoutes.create()">
                        <Plus /> Nueva aseguradora
                    </Link>
                </Button>
            </div>

            <TableToolbar
                v-model:search="filters.search"
                placeholder="Buscar por nombre o código"
                :can-reset="hasActiveFilters"
                @reset="reset"
            />

            <div class="overflow-x-auto rounded-lg border">
                <table class="cards w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Aseguradora</th>
                            <th class="px-4 py-3 font-medium">Pacientes</th>
                            <th class="px-4 py-3 font-medium">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="props.insurers.data.length === 0">
                            <td
                                colspan="3"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No se encontraron aseguradoras.
                            </td>
                        </tr>
                        <tr
                            v-for="insurer in props.insurers.data"
                            :key="insurer.id"
                        >
                            <td data-label="Aseguradora" class="px-4 py-3">
                                <p class="font-medium">{{ insurer.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    Código:
                                    {{ insurer.code ?? 'sin registrar' }}
                                </p>
                            </td>
                            <td
                                data-label="Pacientes"
                                class="px-4 py-3 tabular-nums"
                            >
                                {{ insurer.patients_count }}
                            </td>
                            <td data-label="" class="px-4 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <IconButton
                                        label="Editar"
                                        tone="info"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                insurerRoutes.edit(insurer.id)
                                            "
                                        >
                                            <Pencil />
                                        </Link>
                                    </IconButton>
                                    <IconButton
                                        v-if="insurer.can_delete"
                                        label="Eliminar"
                                        tone="danger"
                                        @click="
                                            destroy(insurer.id, insurer.name)
                                        "
                                    >
                                        <Trash2 />
                                    </IconButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="props.insurers" />

            <p class="text-xs text-muted-foreground">
                Solo se pueden eliminar las aseguradoras sin pacientes
                asignados.
            </p>
        </div>
    </AppLayout>
</template>
