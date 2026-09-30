<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import DoctorScheduleController from '@/actions/App/Http/Controllers/DoctorScheduleController';
import InputError from '@/components/InputError.vue';
import TimeSelect from '@/components/TimeSelect.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction } from '@/lib/confirm';
import { formatTime } from '@/lib/format';
import doctorRoutes from '@/routes/doctors';
import scheduleRoutes from '@/routes/schedules';
import type { BreadcrumbItem } from '@/types';

type Schedule = {
    id: number;
    day_of_week: number;
    day_name: string;
    starts_at: string;
    ends_at: string;
};

const props = defineProps<{
    doctor: { id: number; name: string; specialty: string };
    schedules: Schedule[];
    days: { value: number; label: string }[];
}>();

const page = usePage();
const isOwnSchedule = computed(
    () => page.props.auth.doctorId === props.doctor.id,
);

const breadcrumbs: BreadcrumbItem[] = isOwnSchedule.value
    ? [{ title: 'Mi horario', href: scheduleRoutes.index(props.doctor.id) }]
    : [
          { title: 'Médicos', href: doctorRoutes.index() },
          {
              title: `Horario · ${props.doctor.name}`,
              href: scheduleRoutes.index(props.doctor.id),
          },
      ];

const schedulesByDay = computed(() =>
    props.days
        .map((day) => ({
            ...day,
            blocks: props.schedules.filter(
                (schedule) => schedule.day_of_week === day.value,
            ),
        }))
        .filter((day) => day.blocks.length > 0),
);

async function destroy(schedule: Schedule) {
    const accepted = await confirmAction({
        title: 'Eliminar bloque de horario',
        text: `${schedule.day_name} ${formatTime(schedule.starts_at)} – ${formatTime(schedule.ends_at)}.`,
        confirmText: 'Sí, eliminar',
        cancelText: 'Volver',
        tone: 'danger',
    });

    if (accepted) {
        router.delete(scheduleRoutes.destroy(schedule.id).url, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head :title="isOwnSchedule ? 'Mi horario' : `Horario · ${doctor.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{
                        isOwnSchedule
                            ? 'Mi horario de atención'
                            : `Horario de ${doctor.name}`
                    }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ doctor.specialty }} · Las citas solo podrán agendarse
                    dentro de estos bloques. Sin bloques registrados, se aceptan
                    citas en cualquier horario.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Agregar bloque</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="DoctorScheduleController.store.form(doctor.id)"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        class="grid gap-4 sm:grid-cols-4 sm:items-end"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="day_of_week">Día</Label>
                            <NativeSelect
                                id="day_of_week"
                                name="day_of_week"
                                default-value="1"
                            >
                                <option
                                    v-for="day in days"
                                    :key="day.value"
                                    :value="day.value"
                                >
                                    {{ day.label }}
                                </option>
                            </NativeSelect>
                            <InputError :message="errors.day_of_week" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="starts_at">Desde</Label>
                            <TimeSelect
                                id="starts_at"
                                name="starts_at"
                                default-value="08:00"
                                required
                            />
                            <InputError :message="errors.starts_at" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ends_at">Hasta</Label>
                            <TimeSelect
                                id="ends_at"
                                name="ends_at"
                                default-value="12:00"
                                required
                            />
                            <InputError :message="errors.ends_at" />
                        </div>
                        <Button :disabled="processing"><Plus /> Agregar</Button>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Horario semanal</CardTitle>
                    <CardDescription v-if="schedulesByDay.length === 0">
                        No hay bloques registrados.
                    </CardDescription>
                </CardHeader>
                <CardContent v-if="schedulesByDay.length" class="divide-y">
                    <div
                        v-for="day in schedulesByDay"
                        :key="day.value"
                        class="flex flex-wrap items-center gap-3 py-3"
                    >
                        <p class="w-28 font-medium">{{ day.label }}</p>
                        <span
                            v-for="block in day.blocks"
                            :key="block.id"
                            class="inline-flex items-center gap-1 rounded-full border py-1 pr-1 pl-3 text-sm tabular-nums"
                        >
                            {{ formatTime(block.starts_at) }} –
                            {{ formatTime(block.ends_at) }}
                            <button
                                type="button"
                                class="rounded-full p-1 text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-500/20"
                                :aria-label="`Eliminar bloque ${block.day_name} ${formatTime(block.starts_at)}`"
                                @click="destroy(block)"
                            >
                                <Trash2 class="size-3.5" />
                            </button>
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
