<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppointmentController from '@/actions/App/Http/Controllers/AppointmentController';
import SlotPicker from '@/components/appointments/SlotPicker.vue';
import InputError from '@/components/InputError.vue';
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
        slot_minutes: number;
        schedule_summary: { days: string; ranges: string[] }[];
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

const scheduledAt = ref('');

const selectedDoctorNumericId = computed(() =>
    selectedDoctorId.value ? Number(selectedDoctorId.value) : null,
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
                        class="overflow-hidden rounded-lg border"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/50 px-4 py-2.5"
                        >
                            <p class="text-sm font-semibold">
                                Horario de atención
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Citas de {{ selectedDoctor.slot_minutes }} min ·
                                {{
                                    formatMoney(selectedDoctor.consultation_fee)
                                }}
                            </p>
                        </div>
                        <ul
                            v-if="selectedDoctor.schedule_summary.length"
                            class="divide-y"
                        >
                            <li
                                v-for="row in selectedDoctor.schedule_summary"
                                :key="row.days"
                                class="flex flex-wrap items-center gap-x-4 gap-y-1.5 px-4 py-2.5 text-sm"
                            >
                                <span class="w-24 shrink-0 font-medium">{{
                                    row.days
                                }}</span>
                                <span class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="range in row.ranges"
                                        :key="range"
                                        class="rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium whitespace-nowrap text-emerald-800 tabular-nums dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300"
                                        >{{ range }}</span
                                    >
                                </span>
                            </li>
                        </ul>
                        <p
                            v-else
                            class="px-4 py-3 text-sm text-muted-foreground"
                        >
                            Este médico no tiene un horario registrado; se
                            aceptan citas en cualquier horario.
                        </p>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>Fecha y hora *</Label>
                    <SlotPicker
                        v-model="scheduledAt"
                        :doctor-id="selectedDoctorNumericId"
                    />
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
                    <Button :disabled="processing || !scheduledAt">
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
