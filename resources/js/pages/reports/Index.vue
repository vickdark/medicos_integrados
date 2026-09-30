<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileSpreadsheet, FileText } from 'lucide-vue-next';
import { computed } from 'vue';
import DateRangeFilter from '@/components/DateRangeFilter.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMoney } from '@/lib/format';
import reportRoutes from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';
import type { Option } from '@/types/models';

type Cell = string | number | null;

const props = defineProps<{
    report: {
        title: string;
        headings: string[];
        rows: Cell[][];
        totals: Cell[];
        money_columns: number[];
    };
    filters: {
        type: string;
        group: string;
        from: string;
        to: string;
        doctor_id: number | null;
        specialty_id: number | null;
    };
    restricted: boolean;
    types: Option[];
    groups: Option[];
    doctors: { value: number; label: string }[];
    specialties: { value: number; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reportes', href: reportRoutes.index() },
];

const { filters, activeFilters } = useTableFilters(
    () => reportRoutes.index().url,
    {
        type: props.filters.type,
        group: props.filters.group,
        from: props.filters.from,
        to: props.filters.to,
        doctor_id: props.filters.doctor_id,
        specialty_id: props.filters.specialty_id,
    },
);

const exportUrl = (format: 'xlsx' | 'pdf') =>
    reportRoutes.export({ query: { ...activeFilters.value, format } }).url;

const doctorOptions = computed(() => [
    { value: '', label: 'Todos los médicos' },
    ...props.doctors,
]);

function formatCell(value: Cell, column: number): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (props.report.money_columns.includes(column)) {
        return formatMoney(value);
    }

    return String(value);
}

const isNumeric = (column: number) =>
    props.report.rows.some((row) => typeof row[column] === 'number');
</script>

<template>
    <Head title="Reportes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ restricted ? 'Mis reportes' : 'Reportes' }}
                </h1>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <a :href="exportUrl('xlsx')" data-test="export-xlsx">
                            <FileSpreadsheet class="text-emerald-600" /> Excel
                        </a>
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <a :href="exportUrl('pdf')" data-test="export-pdf">
                            <FileText class="text-red-600" /> PDF
                        </a>
                    </Button>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-1">
                    <Label class="text-xs text-muted-foreground">Reporte</Label>
                    <div class="inline-flex rounded-md border p-0.5">
                        <Button
                            v-for="type in types"
                            :key="type.value"
                            size="sm"
                            :variant="
                                filters.type === type.value ? 'default' : 'ghost'
                            "
                            @click="filters.type = type.value"
                        >
                            {{ type.label }}
                        </Button>
                    </div>
                </div>

                <div v-if="!restricted" class="grid gap-1">
                    <Label for="report-group" class="text-xs text-muted-foreground"
                        >Agrupar</Label
                    >
                    <NativeSelect
                        id="report-group"
                        v-model="filters.group"
                        class="w-44"
                    >
                        <option
                            v-for="group in groups"
                            :key="group.value"
                            :value="group.value"
                        >
                            {{ group.label }}
                        </option>
                    </NativeSelect>
                </div>

                <DateRangeFilter
                    v-model:from="filters.from"
                    v-model:to="filters.to"
                />

                <div v-if="!restricted" class="grid w-56 gap-1">
                    <Label for="report-doctor" class="text-xs text-muted-foreground"
                        >Médico</Label
                    >
                    <SearchableSelect
                        id="report-doctor"
                        v-model="filters.doctor_id"
                        :options="doctorOptions"
                        search-placeholder="Buscar médico"
                    />
                </div>

                <div v-if="!restricted" class="grid gap-1">
                    <Label
                        for="report-specialty"
                        class="text-xs text-muted-foreground"
                        >Especialidad</Label
                    >
                    <NativeSelect
                        id="report-specialty"
                        v-model="filters.specialty_id"
                        class="w-48"
                    >
                        <option value="">Todas</option>
                        <option
                            v-for="specialty in specialties"
                            :key="specialty.value"
                            :value="specialty.value"
                        >
                            {{ specialty.label }}
                        </option>
                    </NativeSelect>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border">
                <table class="cards w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th
                                v-for="(heading, column) in report.headings"
                                :key="heading"
                                class="px-4 py-3 font-medium"
                                :class="{ 'text-right': isNumeric(column) }"
                            >
                                {{ heading }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="report.rows.length === 0">
                            <td
                                :colspan="report.headings.length"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No hay datos para el período y los filtros
                                seleccionados.
                            </td>
                        </tr>
                        <tr v-for="(row, index) in report.rows" :key="index">
                            <td
                                v-for="(cell, column) in row"
                                :key="column"
                                :data-label="report.headings[column]"
                                class="px-4 py-3"
                                :class="{
                                    'text-right tabular-nums': isNumeric(column),
                                    'font-medium': column === 0,
                                }"
                            >
                                {{ formatCell(cell, column) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot
                        v-if="report.totals.length"
                        class="border-t-2 bg-muted/30 font-semibold"
                    >
                        <tr>
                            <td
                                v-for="(cell, column) in report.totals"
                                :key="column"
                                :data-label="report.headings[column]"
                                class="px-4 py-3"
                                :class="{
                                    'text-right tabular-nums': isNumeric(column),
                                }"
                            >
                                {{ cell === null ? '' : formatCell(cell, column) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="text-xs text-muted-foreground">
                Las citas se cuentan por su fecha programada y las consultas por
                su fecha de atención. Los ingresos cobrados se cuentan por fecha
                de pago y los pendientes por la fecha en que se generaron; los
                pagos anulados no se incluyen.
            </p>
        </div>
    </AppLayout>
</template>
