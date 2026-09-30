<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppointmentController from '@/actions/App/Http/Controllers/AppointmentController';
import InputError from '@/components/InputError.vue';
import TimeSelect from '@/components/TimeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMoney } from '@/lib/format';
import appointmentRoutes from '@/routes/appointments';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    doctors: {
        id: number;
        name: string;
        specialty: string;
        consultation_fee: string;
        schedules: string[];
    }[];
    patients: {
        id: number;
        full_name: string;
        document_number: string | null;
    }[];
    selectedPatientId: number | null;
    isStaff: boolean;
}>();

const title = props.isStaff ? 'Agendar cita' : 'Solicitar cita';

const selectedDoctorId = ref<string | number | null>('');
const selectedDoctor = computed(() =>
    props.doctors.find(
        (doctor) => doctor.id === Number(selectedDoctorId.value),
    ),
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Citas', href: appointmentRoutes.index() },
    { title, href: appointmentRoutes.create() },
];

const today = new Date(Date.now() - new Date().getTimezoneOffset() * 60000)
    .toISOString()
    .slice(0, 10);

const appointmentDate = ref('');
const appointmentTime = ref('');

const scheduledAt = computed(() =>
    appointmentDate.value && appointmentTime.value
        ? `${appointmentDate.value}T${appointmentTime.value}`
        : '',
);
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <p v-if="!isStaff" class="text-sm text-muted-foreground">
                    La clínica revisará tu solicitud y la confirmará lo antes
                    posible.
                </p>
            </div>

            <Form
                v-bind="AppointmentController.store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div v-if="isStaff" class="grid gap-2">
                    <Label for="patient_id">Paciente *</Label>
                    <NativeSelect
                        id="patient_id"
                        name="patient_id"
                        :default-value="selectedPatientId ?? ''"
                        required
                    >
                        <option value="" disabled>
                            Selecciona un paciente
                        </option>
                        <option
                            v-for="patient in patients"
                            :key="patient.id"
                            :value="patient.id"
                        >
                            {{ patient.full_name
                            }}{{
                                patient.document_number
                                    ? ` · ${patient.document_number}`
                                    : ''
                            }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.patient_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="doctor_id">Médico *</Label>
                    <NativeSelect
                        id="doctor_id"
                        v-model="selectedDoctorId"
                        name="doctor_id"
                        required
                    >
                        <option value="" disabled>Selecciona un médico</option>
                        <option
                            v-for="doctor in doctors"
                            :key="doctor.id"
                            :value="doctor.id"
                        >
                            {{ doctor.name }} · {{ doctor.specialty }} ({{
                                formatMoney(doctor.consultation_fee)
                            }})
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.doctor_id" />
                    <div
                        v-if="selectedDoctor"
                        class="rounded-md bg-muted/50 px-3 py-2 text-xs text-muted-foreground"
                    >
                        <template v-if="selectedDoctor.schedules.length">
                            <span class="font-medium text-foreground"
                                >Horario de atención:</span
                            >
                            {{ selectedDoctor.schedules.join(' · ') }}
                        </template>
                        <template v-else>
                            Este médico no tiene un horario registrado.
                        </template>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="appointment_date">Fecha y hora *</Label>
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

                <div class="grid gap-2">
                    <Label for="reason">Motivo de la consulta *</Label>
                    <Input
                        id="reason"
                        name="reason"
                        placeholder="Ej. control general, dolor de cabeza…"
                        required
                    />
                    <InputError :message="errors.reason" />
                </div>

                <div class="grid gap-2">
                    <Label for="notes">Notas adicionales</Label>
                    <Textarea id="notes" name="notes" />
                    <InputError :message="errors.notes" />
                </div>

                <div class="flex gap-2">
                    <Button :disabled="processing">
                        {{ isStaff ? 'Agendar cita' : 'Enviar solicitud' }}
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="appointmentRoutes.index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
