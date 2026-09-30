<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import DateRangeFilter from '@/components/DateRangeFilter.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatMoney } from '@/lib/format';
import patientRoutes from '@/routes/patients';
import paymentRoutes from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';
import type { Option, Paginated, Payment, TableFilters } from '@/types/models';

const props = defineProps<{
    payments: Paginated<Payment>;
    totals: { paid: number; pending: number };
    filters: TableFilters;
    statuses: Option[];
    can: { create: boolean };
}>();

const { filters, activeFilters, hasActiveFilters, reset } = useTableFilters(
    () => paymentRoutes.index().url,
    {
        search: props.filters.search,
        status: props.filters.status,
        from: props.filters.from,
        to: props.filters.to,
    },
);

const exportUrl = (format: 'xlsx' | 'pdf') =>
    paymentRoutes.export({ query: { ...activeFilters.value, format } }).url;

const page = usePage();
const isPatient = computed(() => page.props.auth.role?.value === 'patient');
const title = computed(() => (isPatient.value ? 'Mis pagos' : 'Pagos'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pagos', href: paymentRoutes.index() },
];
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <Button v-if="can.create" as-child>
                    <Link :href="paymentRoutes.create()"
                        ><Plus /> Registrar pago</Link
                    >
                </Button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:max-w-2xl">
                <Card class="gap-2">
                    <CardHeader>
                        <CardDescription>Total pagado</CardDescription>
                        <CardTitle class="text-2xl tabular-nums">{{
                            formatMoney(totals.paid)
                        }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card class="gap-2">
                    <CardHeader>
                        <CardDescription>Pendiente de pago</CardDescription>
                        <CardTitle
                            class="text-2xl text-amber-600 tabular-nums dark:text-amber-400"
                        >
                            {{ formatMoney(totals.pending) }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <TableToolbar
                v-model:search="filters.search"
                :placeholder="
                    isPatient
                        ? 'Buscar por concepto o referencia'
                        : 'Buscar por paciente, concepto o referencia'
                "
                :export-url="exportUrl"
                :can-reset="hasActiveFilters"
                @reset="reset"
            >
                <template #filters>
                    <div class="grid gap-1">
                        <Label
                            for="filter-status"
                            class="text-xs text-muted-foreground"
                            >Estado</Label
                        >
                        <NativeSelect
                            id="filter-status"
                            v-model="filters.status"
                            class="w-36"
                        >
                            <option value="">Todos</option>
                            <option
                                v-for="status in statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </option>
                        </NativeSelect>
                    </div>
                    <DateRangeFilter
                        v-model:from="filters.from"
                        v-model:to="filters.to"
                    />
                </template>
            </TableToolbar>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Fecha</th>
                            <th v-if="!isPatient" class="px-4 py-3 font-medium">
                                Paciente
                            </th>
                            <th class="px-4 py-3 font-medium">Concepto</th>
                            <th class="px-4 py-3 font-medium">Método</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Monto
                            </th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="props.payments.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No hay pagos registrados.
                            </td>
                        </tr>
                        <tr
                            v-for="payment in props.payments.data"
                            :key="payment.id"
                        >
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDate(payment.paid_at) }}
                            </td>
                            <td v-if="!isPatient" class="px-4 py-3">
                                <Link
                                    :href="
                                        patientRoutes.show(payment.patient!.id)
                                    "
                                    class="font-medium hover:underline"
                                >
                                    {{ payment.patient?.full_name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                {{ payment.concept }}
                                <p
                                    v-if="payment.reference"
                                    class="text-xs text-muted-foreground"
                                >
                                    Ref. {{ payment.reference }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                {{ payment.method.label }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-medium tabular-nums"
                            >
                                {{ formatMoney(payment.amount) }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="payment.status" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="props.payments" />
        </div>
    </AppLayout>
</template>
