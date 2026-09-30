<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import DateRangeFilter from '@/components/DateRangeFilter.vue';
import Pagination from '@/components/Pagination.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/format';
import auditLogRoutes from '@/routes/audit-logs';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type { Option, Paginated, TableFilters } from '@/types/models';

type AuditLogEntry = {
    id: number;
    action: Option;
    description: string;
    user: { name: string; role: string } | null;
    patient: { id: number; full_name: string } | null;
    ip_address: string | null;
    created_at: string;
};

const props = defineProps<{
    logs: Paginated<AuditLogEntry>;
    filters: TableFilters;
    patient: { id: number; full_name: string } | null;
    actions: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auditoría', href: auditLogRoutes.index() },
];

const { filters, activeFilters, hasActiveFilters, reset } = useTableFilters(
    () => auditLogRoutes.index().url,
    {
        search: props.filters.search,
        action: props.filters.action,
        patient_id: props.filters.patient_id,
        from: props.filters.from,
        to: props.filters.to,
    },
);

const exportUrl = (format: 'xlsx' | 'pdf') =>
    auditLogRoutes.export({ query: { ...activeFilters.value, format } }).url;
</script>

<template>
    <Head title="Auditoría" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Auditoría de accesos
                </h1>
                <p class="text-sm text-muted-foreground">
                    Registro de quién consultó o modificó información clínica.
                </p>
            </div>

            <TableToolbar
                v-model:search="filters.search"
                placeholder="Buscar por usuario, paciente, descripción o IP"
                :export-url="exportUrl"
                :can-reset="hasActiveFilters"
                @reset="reset"
            >
                <template #filters>
                    <div class="grid gap-1">
                        <Label
                            for="filter-action"
                            class="text-xs text-muted-foreground"
                            >Acción</Label
                        >
                        <NativeSelect
                            id="filter-action"
                            v-model="filters.action"
                            class="w-36"
                        >
                            <option value="">Todas</option>
                            <option
                                v-for="action in actions"
                                :key="action.value"
                                :value="action.value"
                            >
                                {{ action.label }}
                            </option>
                        </NativeSelect>
                    </div>
                    <DateRangeFilter
                        v-model:from="filters.from"
                        v-model:to="filters.to"
                    />
                </template>
            </TableToolbar>

            <div v-if="patient && filters.patient_id">
                <span
                    class="inline-flex items-center gap-1 rounded-full border bg-muted px-3 py-1 text-sm"
                >
                    Paciente: {{ patient.full_name }}
                    <button
                        type="button"
                        aria-label="Quitar filtro de paciente"
                        @click="filters.patient_id = null"
                    >
                        <X class="size-3.5" />
                    </button>
                </span>
            </div>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Fecha</th>
                            <th class="px-4 py-3 font-medium">Usuario</th>
                            <th class="px-4 py-3 font-medium">Acción</th>
                            <th class="px-4 py-3 font-medium">Paciente</th>
                            <th class="px-4 py-3 font-medium">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="props.logs.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No hay registros.
                            </td>
                        </tr>
                        <tr v-for="log in props.logs.data" :key="log.id">
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDateTime(log.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ log.user?.name ?? 'Usuario eliminado' }}
                                <p class="text-xs text-muted-foreground">
                                    {{ log.user?.role }}
                                </p>
                            </td>
                            <td class="px-4 py-3">{{ log.description }}</td>
                            <td class="px-4 py-3">
                                <template v-if="log.patient">
                                    <Link
                                        :href="
                                            patientRoutes.show(log.patient.id)
                                        "
                                        class="font-medium hover:underline"
                                    >
                                        {{ log.patient.full_name }}
                                    </Link>
                                    <button
                                        v-if="
                                            filters.patient_id !==
                                            log.patient.id
                                        "
                                        type="button"
                                        class="ml-2 text-xs text-muted-foreground hover:underline"
                                        @click="
                                            filters.patient_id = log.patient.id
                                        "
                                    >
                                        filtrar
                                    </button>
                                </template>
                                <span v-else>—</span>
                            </td>
                            <td
                                class="px-4 py-3 text-xs text-muted-foreground tabular-nums"
                            >
                                {{ log.ip_address ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="props.logs" />
        </div>
    </AppLayout>
</template>
