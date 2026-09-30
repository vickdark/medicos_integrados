<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import { formatClock, formatTime } from '@/lib/format';
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
    doctor: {
        id: number;
        name: string;
        specialty: string;
        slot_minutes: number;
    };
    schedules: Schedule[];
    days: { value: number; label: string }[];
    slotOptions: number[];
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

const HOUR_HEIGHT = 32;
const DEFAULT_START_HOUR = 7;
const DEFAULT_END_HOUR = 19;

const weekOrder = [1, 2, 3, 4, 5, 6, 0];

const orderedDays = computed(() =>
    weekOrder
        .map((value) => props.days.find((day) => day.value === value))
        .filter((day): day is { value: number; label: string } => !!day),
);

const toMinutes = (time: string): number => {
    const [hours, minutes] = time.split(':').map(Number);

    return hours * 60 + minutes;
};

const hourRange = computed(() => {
    let start = DEFAULT_START_HOUR;
    let end = DEFAULT_END_HOUR;

    for (const schedule of props.schedules) {
        start = Math.min(start, Math.floor(toMinutes(schedule.starts_at) / 60));
        end = Math.max(end, Math.ceil(toMinutes(schedule.ends_at) / 60));
    }

    return { start, end };
});

const hours = computed(() =>
    Array.from(
        { length: hourRange.value.end - hourRange.value.start },
        (_, index) => hourRange.value.start + index,
    ),
);

const blockStyle = (schedule: Schedule) => ({
    top: `${((toMinutes(schedule.starts_at) - hourRange.value.start * 60) / 60) * HOUR_HEIGHT}px`,
    height: `${((toMinutes(schedule.ends_at) - toMinutes(schedule.starts_at)) / 60) * HOUR_HEIGHT}px`,
});

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

const selectedDays = ref<number[]>([1]);

function toggleDay(day: number) {
    selectedDays.value = selectedDays.value.includes(day)
        ? selectedDays.value.filter((value) => value !== day)
        : [...selectedDays.value, day];
}

const presets = [
    { label: 'Lunes a viernes', days: [1, 2, 3, 4, 5] },
    { label: 'Lunes a sábado', days: [1, 2, 3, 4, 5, 6] },
];

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
        <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4">
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
                    <CardTitle>Duración de cada cita</CardTitle>
                    <CardDescription>
                        Define los espacios del calendario: con
                        {{ doctor.slot_minutes }} minutos, un bloque de 8:00 a
                        12:00 ofrece
                        {{ Math.floor(240 / doctor.slot_minutes) }} citas.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="
                            DoctorScheduleController.updateSlot.form(doctor.id)
                        "
                        :options="{ preserveScroll: true }"
                        class="flex flex-wrap items-end gap-3"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="slot_minutes">Minutos por cita</Label>
                            <NativeSelect
                                id="slot_minutes"
                                name="slot_minutes"
                                :default-value="doctor.slot_minutes"
                                class="w-40"
                            >
                                <option
                                    v-for="minutes in slotOptions"
                                    :key="minutes"
                                    :value="minutes"
                                >
                                    {{ minutes }} minutos
                                </option>
                            </NativeSelect>
                            <InputError :message="errors.slot_minutes" />
                        </div>
                        <Button :disabled="processing" variant="outline">
                            Guardar
                        </Button>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Agregar horario de atención</CardTitle>
                    <CardDescription>
                        Elige uno o varios días y el rango de horas. Se crea el
                        mismo bloque en cada día elegido.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="DoctorScheduleController.store.form(doctor.id)"
                        :options="{ preserveScroll: true }"
                        class="grid gap-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label>Días</Label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="day in orderedDays"
                                    :key="day.value"
                                    type="button"
                                    class="rounded-full border px-3 py-1 text-sm transition-colors"
                                    :class="
                                        selectedDays.includes(day.value)
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'hover:bg-accent'
                                    "
                                    :aria-pressed="
                                        selectedDays.includes(day.value)
                                    "
                                    @click="toggleDay(day.value)"
                                >
                                    {{ day.label }}
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="preset in presets"
                                    :key="preset.label"
                                    type="button"
                                    class="text-xs text-muted-foreground underline-offset-2 hover:underline"
                                    @click="selectedDays = [...preset.days]"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>
                            <input
                                v-for="day in selectedDays"
                                :key="day"
                                type="hidden"
                                name="days[]"
                                :value="day"
                            />
                            <InputError :message="errors.days" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3 sm:items-end">
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
                            <Button
                                :disabled="processing || !selectedDays.length"
                            >
                                <Plus /> Agregar
                            </Button>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <div
                class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start"
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Vista semanal</CardTitle>
                        <CardDescription v-if="schedules.length === 0">
                            No hay bloques registrados.
                        </CardDescription>
                    </CardHeader>
                    <CardContent v-if="schedules.length" class="grid gap-4">
                        <div class="overflow-x-auto rounded-lg border">
                            <div
                                class="grid min-w-[36rem]"
                                style="
                                    grid-template-columns: 4rem repeat(
                                            7,
                                            minmax(0, 1fr)
                                        );
                                "
                            >
                                <div class="border-b bg-muted/50" />
                                <div
                                    v-for="day in orderedDays"
                                    :key="day.value"
                                    class="border-b border-l bg-muted/50 py-2 text-center text-xs text-muted-foreground"
                                >
                                    {{ day.label.slice(0, 3) }}
                                </div>

                                <div class="pt-2">
                                    <div
                                        v-for="hour in hours"
                                        :key="hour"
                                        class="relative pr-2 text-right text-[11px] text-muted-foreground tabular-nums"
                                        :style="{ height: `${HOUR_HEIGHT}px` }"
                                    >
                                        <span class="absolute -top-2 right-2">{{
                                            formatClock(hour, 0)
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    v-for="day in orderedDays"
                                    :key="day.value"
                                    class="relative mt-2 border-l bg-[repeating-linear-gradient(45deg,transparent,transparent_5px,var(--border)_5px,var(--border)_6px)]"
                                    :style="{
                                        height: `${hours.length * HOUR_HEIGHT}px`,
                                    }"
                                >
                                    <div
                                        v-for="schedule in schedules.filter(
                                            (item) =>
                                                item.day_of_week === day.value,
                                        )"
                                        :key="schedule.id"
                                        class="absolute inset-x-0.5 overflow-hidden rounded border border-emerald-300 bg-emerald-100 px-1 py-0.5 text-[10px] leading-tight text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/20 dark:text-emerald-200"
                                        :style="blockStyle(schedule)"
                                    >
                                        {{ formatTime(schedule.starts_at)
                                        }}<br />{{
                                            formatTime(schedule.ends_at)
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Las franjas rayadas son horas en las que no se
                            atiende.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Horarios de atención</CardTitle>
                        <CardDescription v-if="schedulesByDay.length === 0">
                            No hay bloques registrados.
                        </CardDescription>
                    </CardHeader>
                    <CardContent v-if="schedulesByDay.length" class="divide-y">
                        <div
                            v-for="day in schedulesByDay"
                            :key="day.value"
                            class="grid gap-2 py-3 first:pt-0 last:pb-0"
                        >
                            <p class="text-sm font-medium">{{ day.label }}</p>
                            <div class="flex flex-wrap gap-2">
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
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
