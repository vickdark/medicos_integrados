<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppointmentActions from '@/components/appointments/AppointmentActions.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatClock, formatDateTime } from '@/lib/format';
import patientRoutes from '@/routes/patients';
import type { Appointment } from '@/types/models';

defineProps<{
    appointments: Appointment[];
    role: string;
    emptyText: string;
    /** Show the full date instead of only the time. */
    withDate?: boolean;
    showDoctor?: boolean;
}>();

function clock(appointment: Appointment): string {
    const date = new Date(appointment.scheduled_at);

    return formatClock(date.getHours(), date.getMinutes());
}
</script>

<template>
    <p
        v-if="appointments.length === 0"
        class="py-8 text-center text-sm text-muted-foreground"
    >
        {{ emptyText }}
    </p>

    <ul v-else class="divide-y">
        <li
            v-for="appointment in appointments"
            :key="appointment.id"
            class="grid gap-2 py-3 sm:grid-cols-[6rem_minmax(0,1fr)_auto] sm:items-center"
        >
            <p class="text-sm font-semibold tabular-nums">
                {{
                    withDate
                        ? formatDateTime(appointment.scheduled_at)
                        : clock(appointment)
                }}
            </p>
            <div class="min-w-0">
                <Link
                    v-if="appointment.patient"
                    :href="patientRoutes.show(appointment.patient.id)"
                    class="block truncate text-sm font-medium hover:underline"
                >
                    {{ appointment.patient.full_name }}
                </Link>
                <p class="truncate text-xs text-muted-foreground">
                    <template v-if="showDoctor && appointment.doctor">
                        {{ appointment.doctor.name }} ·
                    </template>
                    {{ appointment.reason }}
                </p>
            </div>
            <div
                class="flex flex-wrap items-center justify-between gap-2 sm:justify-end"
            >
                <StatusBadge :status="appointment.status" />
                <AppointmentActions :appointment="appointment" :role="role" />
            </div>
        </li>
    </ul>
</template>
