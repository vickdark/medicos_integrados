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
import medicationRoutes from '@/routes/medications';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, TableFilters } from '@/types/models';

const props = defineProps<{
    medications: Paginated<{
        id: number;
        name: string;
        presentation: string | null;
        concentration: string | null;
        description: string | null;
    }>;
    filters: TableFilters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Medicamentos', href: medicationRoutes.index() },
];

const { filters, activeFilters, hasActiveFilters, reset } = useTableFilters(
    () => medicationRoutes.index().url,
    { search: props.filters.search },
);

const exportUrl = (format: 'xlsx' | 'pdf') =>
    medicationRoutes.export({ query: { ...activeFilters.value, format } }).url;

async function destroy(id: number, name: string) {
    const accepted = await confirmAction({
        title: 'Eliminar medicamento',
        text: `Se eliminará «${name}» del catálogo. Las recetas ya emitidas no se modifican.`,
        confirmText: 'Sí, eliminar',
        cancelText: 'Volver',
        tone: 'danger',
    });

    if (accepted) {
        router.delete(medicationRoutes.destroy(id).url, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Medicamentos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Medicamentos
                </h1>
                <Button as-child>
                    <Link :href="medicationRoutes.create()">
                        <Plus /> Nuevo medicamento
                    </Link>
                </Button>
            </div>

            <TableToolbar
                v-model:search="filters.search"
                placeholder="Buscar por nombre, presentación o concentración"
                :export-url="exportUrl"
                :can-reset="hasActiveFilters"
                @reset="reset"
            />

            <div class="overflow-x-auto rounded-lg border">
                <table class="cards w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Medicamento</th>
                            <th class="px-4 py-3 font-medium">Presentación</th>
                            <th class="px-4 py-3 font-medium">Concentración</th>
                            <th class="px-4 py-3 font-medium">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="props.medications.data.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No se encontraron medicamentos.
                            </td>
                        </tr>
                        <tr
                            v-for="medication in props.medications.data"
                            :key="medication.id"
                        >
                            <td data-label="Medicamento" class="px-4 py-3">
                                <p class="font-medium">{{ medication.name }}</p>
                                <p
                                    v-if="medication.description"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ medication.description }}
                                </p>
                            </td>
                            <td data-label="Presentación" class="px-4 py-3">
                                {{ medication.presentation ?? '—' }}
                            </td>
                            <td data-label="Concentración" class="px-4 py-3">
                                {{ medication.concentration ?? '—' }}
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
                                                medicationRoutes.edit(
                                                    medication.id,
                                                )
                                            "
                                        >
                                            <Pencil />
                                        </Link>
                                    </IconButton>
                                    <IconButton
                                        label="Eliminar"
                                        tone="danger"
                                        @click="
                                            destroy(
                                                medication.id,
                                                medication.name,
                                            )
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

            <Pagination :paginator="props.medications" />
        </div>
    </AppLayout>
</template>
