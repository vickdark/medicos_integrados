<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppointmentController from '@/actions/App/Http/Controllers/AppointmentController';
import SlotPicker from '@/components/appointments/SlotPicker.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
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

const scheduledAt = ref('');
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
                    <Label>Elige la nueva fecha y hora *</Label>
                    <SlotPicker
                        v-model="scheduledAt"
                        :doctor-id="appointment.doctor?.id ?? null"
                        :ignore-appointment-id="appointment.id"
                        :current-at="appointment.scheduled_at"
                    />
                    <input
                        type="hidden"
                        name="scheduled_at"
                        :value="scheduledAt"
                    />
                    <InputError :message="errors.scheduled_at" />
                </div>

                <div class="flex gap-2">
                    <Button :disabled="processing || !scheduledAt">
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
