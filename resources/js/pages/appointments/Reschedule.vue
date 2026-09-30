<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppointmentController from '@/actions/App/Http/Controllers/AppointmentController';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TimeSelect from '@/components/TimeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { toISODate } from '@/lib/calendar';
import { formatDateTime } from '@/lib/format';
import appointmentRoutes from '@/routes/appointments';
import type { BreadcrumbItem } from '@/types';
import type { Appointment } from '@/types/models';

const props = defineProps<{
    appointment: Appointment;
    schedules: string[];
    isStaff: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Citas', href: appointmentRoutes.index() },
    {
        title: 'Reprogramar',
        href: appointmentRoutes.edit(props.appointment.id),
    },
];

const current = new Date(props.appointment.scheduled_at);
const today = toISODate(new Date());

const appointmentDate = ref(toISODate(current));
const appointmentTime = ref(
    `${String(current.getHours()).padStart(2, '0')}:${String(current.getMinutes()).padStart(2, '0')}`,
);

const scheduledAt = computed(() =>
    appointmentDate.value && appointmentTime.value
        ? `${appointmentDate.value}T${appointmentTime.value}`
        : '',
);
</script>

<template>
    <Head title="Reprogramar cita" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Reprogramar cita
                </h1>
                <p class="text-sm text-muted-foreground">
                    Elige la nueva fecha y hora. Se avisará por correo a las
                    personas involucradas.
                </p>
            </div>

            <div class="grid gap-1 rounded-lg border bg-muted/40 p-4 text-sm">
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Cita actual
                </p>
                <p class="font-medium">
                    {{ formatDateTime(appointment.scheduled_at) }}
                </p>
                <p v-if="appointment.patient">
                    {{ appointment.patient.full_name }}
                </p>
                <p>
                    {{ appointment.doctor?.name }} ·
                    {{ appointment.doctor?.specialty }}
                </p>
                <p class="text-muted-foreground">{{ appointment.reason }}</p>
                <StatusBadge class="mt-1" :status="appointment.status" />
            </div>

            <p
                v-if="!isStaff"
                class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200"
            >
                Tu cambio de fecha quedará pendiente hasta que la clínica lo
                confirme.
            </p>

            <Form
                v-bind="AppointmentController.update.form(appointment.id)"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="appointment_date">Nueva fecha y hora *</Label>
                    <div
                        v-if="schedules.length"
                        class="rounded-md bg-muted/50 px-3 py-2 text-xs text-muted-foreground"
                    >
                        <span class="font-medium text-foreground"
                            >Horario de atención:</span
                        >
                        {{ schedules.join(' · ') }}
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Input
                            id="appointment_date"
                            v-model="appointmentDate"
                            type="date"
                            :min="today"
                            required
                        />
                        <TimeSelect
                            id="appointment_time"
                            v-model="appointmentTime"
                            required
                        />
                    </div>
                    <input
                        type="hidden"
                        name="scheduled_at"
                        :value="scheduledAt"
                    />
                    <InputError :message="errors.scheduled_at" />
                </div>

                <div class="flex gap-2">
                    <Button :disabled="processing">
                        {{
                            isStaff ? 'Reprogramar cita' : 'Solicitar el cambio'
                        }}
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="appointmentRoutes.index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
