<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, CalendarPlus, List } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppointmentActions from '@/components/appointments/AppointmentActions.vue';
import AppointmentCalendar from '@/components/appointments/AppointmentCalendar.vue';
import DateRangeFilter from '@/components/DateRangeFilter.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Button } from '@/components/ui/button';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/format';
import appointmentRoutes from '@/routes/appointments';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type {
    Appointment,
    Option,
    Paginated,
    RoleValue,
    TableFilters,
} from '@/types/models';

const props = defineProps<{
    appointments: Paginated<Appointment>;
    filters: TableFilters;
    statuses: Option[];
    can: { create: boolean };
}>();

const { filters, activeFilters, hasActiveFilters, reset } = useTableFilters(
    () => appointmentRoutes.index().url,
    {
        search: props.filters.search,
        status: props.filters.status,
        from: props.filters.from,
        to: props.filters.to,
    },
);

const exportUrl = (format: 'xlsx' | 'pdf') =>
    appointmentRoutes.export({ query: { ...activeFilters.value, format } }).url;

const page = usePage();
const role = computed(() => page.props.auth.role?.value as RoleValue);
const title = computed(() =>
    role.value === 'patient'
        ? 'Mis citas'
        : role.value === 'doctor'
          ? 'Mi agenda'
          : 'Citas',
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Citas', href: appointmentRoutes.index() },
];

type ViewMode = 'calendar' | 'list';

const listParams = ['search', 'status', 'from', 'to', 'page'];

function initialMode(): ViewMode {
    try {
        const params = new URLSearchParams(window.location.search);

        if (listParams.some((key) => params.has(key))) {
            return 'list';
        }

        return window.localStorage.getItem('appointments-view') === 'list'
            ? 'list'
            : 'calendar';
    } catch {
        return 'calendar';
    }
}

const mode = ref<ViewMode>(initialMode());

function setMode(value: ViewMode) {
    mode.value = value;

    try {
        window.localStorage.setItem('appointments-view', value);
    } catch {
        // Storage can be unavailable; the choice just is not remembered.
    }
}
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <div class="flex flex-wrap items-center gap-2">
                    <div
                        class="inline-flex rounded-md border p-0.5"
                        role="tablist"
                        aria-label="Tipo de vista"
                    >
                        <Button
                            size="sm"
                            role="tab"
                            :aria-selected="mode === 'calendar'"
                            :variant="mode === 'calendar' ? 'default' : 'ghost'"
                            @click="setMode('calendar')"
                        >
                            <CalendarDays /> Calendario
                        </Button>
                        <Button
                            size="sm"
                            role="tab"
                            :aria-selected="mode === 'list'"
                            :variant="mode === 'list' ? 'default' : 'ghost'"
                            @click="setMode('list')"
                        >
                            <List /> Lista
                        </Button>
                    </div>
                    <Button v-if="can.create && role !== 'doctor'" as-child>
                        <Link :href="appointmentRoutes.create()">
                            <CalendarPlus />
                            {{
                                role === 'patient'
                                    ? 'Solicitar cita'
                                    : 'Agendar cita'
                            }}
                        </Link>
                    </Button>
                </div>
            </div>

            <AppointmentCalendar v-if="mode === 'calendar'" :role="role" />

            <template v-else>
                <div
                    class="flex flex-wrap gap-2"
                    role="tablist"
                    aria-label="Filtrar por estado"
                >
                    <Button
                        size="sm"
                        :variant="filters.status === '' ? 'default' : 'outline'"
                        @click="filters.status = ''"
                    >
                        Todas
                    </Button>
                    <Button
                        v-for="status in statuses"
                        :key="status.value"
                        size="sm"
                        :variant="
                            filters.status === status.value
                                ? 'default'
                                : 'outline'
                        "
                        @click="filters.status = status.value"
                    >
                        {{ status.label }}
                    </Button>
                </div>

                <TableToolbar
                    v-model:search="filters.search"
                    :placeholder="
                        role === 'patient'
                            ? 'Buscar por médico o motivo'
                            : 'Buscar por paciente, documento, médico o motivo'
                    "
                    :export-url="exportUrl"
                    :can-reset="hasActiveFilters"
                    @reset="reset"
                >
                    <template #filters>
                        <DateRangeFilter
                            v-model:from="filters.from"
                            v-model:to="filters.to"
                        />
                    </template>
                </TableToolbar>

                <div class="overflow-x-auto rounded-lg border">
                    <table class="cards w-full text-sm">
                        <thead
                            class="bg-muted/50 text-left text-muted-foreground"
                        >
                            <tr>
                                <th class="px-4 py-3 font-medium">
                                    Fecha y hora
                                </th>
                                <th
                                    v-if="role !== 'patient'"
                                    class="px-4 py-3 font-medium"
                                >
                                    Paciente
                                </th>
                                <th
                                    v-if="role !== 'doctor'"
                                    class="px-4 py-3 font-medium"
                                >
                                    Médico
                                </th>
                                <th class="px-4 py-3 font-medium">Motivo</th>
                                <th class="px-4 py-3 font-medium">Estado</th>
                                <th class="px-4 py-3 font-medium">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-if="props.appointments.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-10 text-center text-muted-foreground"
                                >
                                    No hay citas para mostrar.
                                </td>
                            </tr>
                            <tr
                                v-for="appointment in props.appointments.data"
                                :key="appointment.id"
                            >
                                <td
                                    data-label="Fecha y hora"
                                    class="px-4 py-3 whitespace-nowrap"
                                >
                                    {{
                                        formatDateTime(appointment.scheduled_at)
                                    }}
                                </td>
                                <td
                                    data-label="Paciente"
                                    v-if="role !== 'patient'"
                                    class="px-4 py-3"
                                >
                                    <Link
                                        :href="
                                            patientRoutes.show(
                                                appointment.patient!.id,
                                            )
                                        "
                                        class="font-medium hover:underline"
                                    >
                                        {{ appointment.patient?.full_name }}
                                    </Link>
                                </td>
                                <td
                                    data-label="Médico"
                                    v-if="role !== 'doctor'"
                                    class="px-4 py-3"
                                >
                                    {{ appointment.doctor?.name }}
                                    <p class="text-xs text-muted-foreground">
                                        {{ appointment.doctor?.specialty }}
                                    </p>
                                </td>
                                <td data-label="Motivo" class="px-4 py-3">
                                    {{ appointment.reason }}
                                </td>
                                <td data-label="Estado" class="px-4 py-3">
                                    <StatusBadge :status="appointment.status" />
                                </td>
                                <td data-label="" class="px-4 py-3">
                                    <AppointmentActions
                                        :appointment="appointment"
                                        :role="role"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :paginator="props.appointments" />
            </template>
        </div>
    </AppLayout>
</template>
