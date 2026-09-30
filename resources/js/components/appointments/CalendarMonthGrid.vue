<script setup lang="ts">
import { computed } from 'vue';
import {
    appointmentDots,
    appointmentTones,
} from '@/components/appointments/appointmentTones';
import {
    isSameDay,
    monthGridDays,
    toISODate,
    weekdayShort,
} from '@/lib/calendar';
import { formatClock } from '@/lib/format';
import type { Appointment } from '@/types/models';

const MAX_CHIPS = 3;

const props = defineProps<{
    date: Date;
    appointments: Appointment[];
    role: string;
    compact?: boolean;
}>();

const emit = defineEmits<{
    pick: [date: Date];
    select: [appointment: Appointment];
}>();

const days = computed(() => monthGridDays(props.date));
const weekdayLabels = computed(() =>
    days.value.slice(0, 7).map((day) => weekdayShort(day)),
);

const byDay = computed(() => {
    const groups = new Map<string, Appointment[]>();

    for (const appointment of props.appointments) {
        const key = toISODate(new Date(appointment.scheduled_at));
        groups.set(key, [...(groups.get(key) ?? []), appointment]);
    }

    return groups;
});

const eventsOf = (day: Date): Appointment[] =>
    byDay.value.get(toISODate(day)) ?? [];

const isToday = (day: Date) => isSameDay(day, new Date());
const isSelected = (day: Date) => isSameDay(day, props.date);
const isOutside = (day: Date) => day.getMonth() !== props.date.getMonth();

function chipLabel(appointment: Appointment): string {
    const date = new Date(appointment.scheduled_at);
    const name =
        props.role === 'patient'
            ? appointment.doctor?.name
            : appointment.patient?.full_name;

    return `${formatClock(date.getHours(), date.getMinutes())} ${name ?? ''}`;
}
</script>

<template>
    <div class="overflow-hidden rounded-lg border">
        <div
            class="grid grid-cols-7 border-b bg-muted/50 text-center text-muted-foreground"
            :class="compact ? 'py-1 text-[11px]' : 'py-2 text-xs'"
        >
            <span
                v-for="label in weekdayLabels"
                :key="label"
                class="capitalize"
                >{{ compact ? label.slice(0, 1) : label }}</span
            >
        </div>

        <div class="grid grid-cols-7">
            <div
                v-for="day in days"
                :key="day.toISOString()"
                class="border-t border-l [&:nth-child(7n+1)]:border-l-0"
                :class="[
                    isOutside(day) ? 'bg-muted/30 text-muted-foreground' : '',
                    compact ? '' : 'min-h-28 p-1.5',
                ]"
            >
                <button
                    v-if="compact"
                    type="button"
                    class="flex h-11 w-full flex-col items-center justify-center gap-0.5 text-sm tabular-nums"
                    :class="
                        isSelected(day)
                            ? 'bg-primary text-primary-foreground'
                            : isToday(day)
                              ? 'font-semibold text-primary'
                              : ''
                    "
                    :aria-label="
                        day.toLocaleDateString('es', { dateStyle: 'full' })
                    "
                    @click="emit('pick', day)"
                >
                    {{ day.getDate() }}
                    <span class="flex h-1.5 gap-0.5">
                        <span
                            v-for="appointment in eventsOf(day).slice(0, 3)"
                            :key="appointment.id"
                            class="size-1.5 rounded-full"
                            :class="
                                isSelected(day)
                                    ? 'bg-primary-foreground'
                                    : appointmentDots[appointment.status.value]
                            "
                        />
                    </span>
                </button>

                <template v-else>
                    <button
                        type="button"
                        class="mb-1 flex size-6 items-center justify-center rounded-full text-xs tabular-nums hover:bg-accent"
                        :class="
                            isToday(day)
                                ? 'bg-primary font-semibold text-primary-foreground hover:bg-primary/90'
                                : ''
                        "
                        @click="emit('pick', day)"
                    >
                        {{ day.getDate() }}
                    </button>
                    <div class="grid gap-0.5">
                        <button
                            v-for="appointment in eventsOf(day).slice(
                                0,
                                MAX_CHIPS,
                            )"
                            :key="appointment.id"
                            type="button"
                            class="truncate rounded border-l-4 px-1 py-0.5 text-left text-[11px] leading-tight hover:opacity-80"
                            :class="appointmentTones[appointment.status.value]"
                            @click="emit('select', appointment)"
                        >
                            {{ chipLabel(appointment) }}
                        </button>
                        <button
                            v-if="eventsOf(day).length > MAX_CHIPS"
                            type="button"
                            class="text-left text-[11px] text-muted-foreground hover:underline"
                            @click="emit('pick', day)"
                        >
                            +{{ eventsOf(day).length - MAX_CHIPS }} más
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
