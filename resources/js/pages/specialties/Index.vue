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
import specialtyRoutes from '@/routes/specialties';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, TableFilters } from '@/types/models';

const props = defineProps<{
    specialties: Paginated<{
        id: number;
        name: string;
        description: string | null;
        doctors_count: number;
        can_delete: boolean;
    }>;
    filters: TableFilters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Especialidades', href: specialtyRoutes.index() },
];

const { filters, activeFilters, hasActiveFilters, reset } = useTableFilters(
    () => specialtyRoutes.index().url,
    { search: props.filters.search },
);

const exportUrl = (format: 'xlsx' | 'pdf') =>
    specialtyRoutes.export({ query: { ...activeFilters.value, format } }).url;

async function destroy(id: number, name: string) {
    const accepted = await confirmAction({
        title: 'Eliminar especialidad',
        text: `Se eliminará «${name}». Esta acción no se puede deshacer.`,
        confirmText: 'Sí, eliminar',
        cancelText: 'Volver',
        tone: 'danger',
    });

    if (accepted) {
        router.delete(specialtyRoutes.destroy(id).url, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Especialidades" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Especialidades
                </h1>
                <Button as-child>
                    <Link :href="specialtyRoutes.create()">
                        <Plus /> Nueva especialidad
                    </Link>
                </Button>
            </div>

            <TableToolbar
                v-model:search="filters.search"
                placeholder="Buscar especialidad"
                :export-url="exportUrl"
                :can-reset="hasActiveFilters"
                @reset="reset"
            />

            <div class="overflow-x-auto rounded-lg border">
                <table class="cards w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Especialidad</th>
                            <th class="px-4 py-3 font-medium">Médicos</th>
                            <th class="px-4 py-3 font-medium">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="props.specialties.data.length === 0">
                            <td
                                colspan="3"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No se encontraron especialidades.
                            </td>
                        </tr>
                        <tr
                            v-for="specialty in props.specialties.data"
                            :key="specialty.id"
                        >
                            <td data-label="Especialidad" class="px-4 py-3">
                                <p class="font-medium">{{ specialty.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ specialty.description ?? '—' }}
                                </p>
                            </td>
                            <td
                                data-label="Médicos"
                                class="px-4 py-3 tabular-nums"
                            >
                                {{ specialty.doctors_count }}
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
                                                specialtyRoutes.edit(
                                                    specialty.id,
                                                )
                                            "
                                        >
                                            <Pencil />
                                        </Link>
                                    </IconButton>
                                    <IconButton
                                        v-if="specialty.can_delete"
                                        label="Eliminar"
                                        tone="danger"
                                        @click="
                                            destroy(
                                                specialty.id,
                                                specialty.name,
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

            <Pagination :paginator="props.specialties" />

            <p class="text-xs text-muted-foreground">
                Solo se pueden eliminar las especialidades sin médicos
                asignados.
            </p>
        </div>
    </AppLayout>
</template>
