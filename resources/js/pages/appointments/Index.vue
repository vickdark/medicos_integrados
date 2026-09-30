<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarPlus } from 'lucide-vue-next';
import { computed } from 'vue';
import DateRangeFilter from '@/components/DateRangeFilter.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Button } from '@/components/ui/button';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/format';
import appointmentRoutes from '@/routes/appointments';
import consultationRoutes from '@/routes/consultations';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type {
    Appointment,
    AppointmentStatusValue,
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
const isStaff = computed(() => role.value !== 'patient');
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

function changeStatus(
    appointment: Appointment,
    status: AppointmentStatusValue,
) {
    if (
        status === 'cancelled' &&
        !confirm('¿Seguro que deseas cancelar esta cita?')
    ) {
        return;
    }

    router.patch(
        appointmentRoutes.status(appointment.id).url,
        { status },
        { preserveScroll: true },
    );
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
                        filters.status === status.value ? 'default' : 'outline'
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
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Fecha y hora</th>
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
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDateTime(appointment.scheduled_at) }}
                            </td>
                            <td v-if="role !== 'patient'" class="px-4 py-3">
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
                            <td v-if="role !== 'doctor'" class="px-4 py-3">
                                {{ appointment.doctor?.name }}
                                <p class="text-xs text-muted-foreground">
                                    {{ appointment.doctor?.specialty }}
                                </p>
                            </td>
                            <td class="px-4 py-3">{{ appointment.reason }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="appointment.status" />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        v-if="appointment.consultation_id"
                                        size="sm"
                                        variant="outline"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                consultationRoutes.show(
                                                    appointment.consultation_id,
                                                )
                                            "
                                            >Ver consulta</Link
                                        >
                                    </Button>
                                    <template
                                        v-if="appointment.can.update_status"
                                    >
                                        <Button
                                            v-if="role === 'doctor'"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    consultationRoutes.create(
                                                        appointment.patient!.id,
                                                        {
                                                            query: {
                                                                appointment_id:
                                                                    appointment.id,
                                                            },
                                                        },
                                                    )
                                                "
                                            >
                                                Atender
                                            </Link>
                                        </Button>
                                        <Button
                                            v-if="
                                                isStaff &&
                                                appointment.status.value ===
                                                    'requested'
                                            "
                                            size="sm"
                                            variant="outline"
                                            @click="
                                                changeStatus(
                                                    appointment,
                                                    'confirmed',
                                                )
                                            "
                                        >
                                            Confirmar
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="text-destructive"
                                            @click="
                                                changeStatus(
                                                    appointment,
                                                    'cancelled',
                                                )
                                            "
                                        >
                                            Cancelar
                                        </Button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="props.appointments" />
        </div>
    </AppLayout>
</template>
