<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { UserPlus } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Button } from '@/components/ui/button';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, Patient, TableFilters } from '@/types/models';

const props = defineProps<{
    patients: Paginated<Patient>;
    filters: TableFilters;
    can: { create: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pacientes', href: patientRoutes.index() },
];

const { filters, activeFilters, hasActiveFilters, reset } = useTableFilters(
    () => patientRoutes.index().url,
    { search: props.filters.search },
);

const exportUrl = (format: 'xlsx' | 'pdf') =>
    patientRoutes.export({ query: { ...activeFilters.value, format } }).url;
</script>

<template>
    <Head title="Pacientes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">Pacientes</h1>
                <Button v-if="can.create" as-child>
                    <Link :href="patientRoutes.create()"
                        ><UserPlus /> Nuevo paciente</Link
                    >
                </Button>
            </div>

            <TableToolbar
                v-model:search="filters.search"
                placeholder="Buscar por nombre, documento o correo"
                :export-url="exportUrl"
                :can-reset="hasActiveFilters"
                @reset="reset"
            />

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Paciente</th>
                            <th class="px-4 py-3 font-medium">Documento</th>
                            <th class="px-4 py-3 font-medium">Edad</th>
                            <th class="px-4 py-3 font-medium">Teléfono</th>
                            <th class="px-4 py-3 font-medium">Portal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="props.patients.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No se encontraron pacientes.
                            </td>
                        </tr>
                        <tr
                            v-for="patient in props.patients.data"
                            :key="patient.id"
                            class="transition-colors hover:bg-muted/40"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="patientRoutes.show(patient.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ patient.full_name }}
                                </Link>
                                <p class="text-xs text-muted-foreground">
                                    {{ patient.email ?? '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                {{ patient.document_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    patient.age !== null
                                        ? `${patient.age} años`
                                        : '—'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                {{ patient.phone ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="text-xs"
                                    :class="
                                        patient.has_account
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-muted-foreground'
                                    "
                                >
                                    {{
                                        patient.has_account
                                            ? 'Con cuenta'
                                            : 'Sin cuenta'
                                    }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="props.patients" />
        </div>
    </AppLayout>
</template>
