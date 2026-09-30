<script setup lang="ts">
import { useIntervalFn } from '@vueuse/core';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { appointmentTones } from '@/components/appointments/appointmentTones';
import {
    isSameDay,
    layoutOverlaps,
    minutesOfDay,
    weekdayShort,
} from '@/lib/calendar';
import { formatClock } from '@/lib/format';
import type { Appointment } from '@/types/models';

const HOUR_HEIGHT = 72;
const DEFAULT_DURATION = 30;
const MIN_START_HOUR = 7;
const MAX_END_HOUR = 20;
const TOP_PADDING = 8;

const props = defineProps<{
    days: Date[];
    appointments: Appointment[];
    role: string;
    showDayHeader?: boolean;
}>();

const emit = defineEmits<{ select: [appointment: Appointment] }>();

const scroller = ref<HTMLElement | null>(null);
const now = ref(new Date());
useIntervalFn(() => (now.value = new Date()), 60_000);

const isSingleDay = computed(() => props.days.length === 1);

const eventsByDay = computed(() =>
    props.days.map((day) =>
        props.appointments
            .map((appointment) => ({
                appointment,
                date: new Date(appointment.scheduled_at),
            }))
            .filter(({ date }) => isSameDay(date, day)),
    ),
);

const hourRange = computed(() => {
    let start = MIN_START_HOUR;
    let end = MAX_END_HOUR;

    for (const entries of eventsByDay.value) {
        for (const { date } of entries) {
            start = Math.min(start, date.getHours());
            end = Math.max(
                end,
                Math.ceil((minutesOfDay(date) + DEFAULT_DURATION) / 60),
            );
        }
    }

    return { start, end: Math.min(end, 24) };
});

const hours = computed(() =>
    Array.from(
        { length: hourRange.value.end - hourRange.value.start },
        (_, index) => hourRange.value.start + index,
    ),
);

const columns = computed(
    () => `4rem repeat(${props.days.length}, minmax(6.5rem, 1fr))`,
);

const offsetOf = (minutes: number): number =>
    ((minutes - hourRange.value.start * 60) / 60) * HOUR_HEIGHT;

const positionedByDay = computed(() =>
    eventsByDay.value.map((entries) =>
        layoutOverlaps(
            entries,
            ({ date }) => minutesOfDay(date),
            ({ date }) => minutesOfDay(date) + DEFAULT_DURATION,
        ).map(({ item, column, columns: total }) => ({
            appointment: item.appointment,
            time: formatClock(item.date.getHours(), item.date.getMinutes()),
            top: offsetOf(minutesOfDay(item.date)),
            style: {
                top: `${offsetOf(minutesOfDay(item.date)) + 1}px`,
                height: `${(DEFAULT_DURATION / 60) * HOUR_HEIGHT - 3}px`,
                left: `calc(${(column / total) * 100}% + 2px)`,
                width: `calc(${100 / total}% - 4px)`,
            },
        })),
    ),
);

const hasAppointments = computed(() => props.appointments.length > 0);

const nowOffset = computed<number | null>(() => {
    const minutes = minutesOfDay(now.value);

    if (
        minutes < hourRange.value.start * 60 ||
        minutes > hourRange.value.end * 60
    ) {
        return null;
    }

    return offsetOf(minutes);
});

const isToday = (day: Date) => isSameDay(day, now.value);

function title(appointment: Appointment): string {
    return props.role === 'patient'
        ? (appointment.doctor?.name ?? '')
        : (appointment.patient?.full_name ?? '');
}

function scrollToRelevantHour() {
    const element = scroller.value;

    if (!element) {
        return;
    }

    const tops = positionedByDay.value.flatMap((entries) =>
        entries.map((entry) => entry.top),
    );
    const target =
        tops.length > 0
            ? Math.min(...tops)
            : props.days.some(isToday) && nowOffset.value !== null
              ? nowOffset.value - HOUR_HEIGHT
              : 0;

    element.scrollTop = Math.max(0, target - HOUR_HEIGHT / 2);
}

onMounted(() => nextTick(scrollToRelevantHour));

watch(
    () => [props.days[0]?.getTime(), props.days.length, props.appointments],
    () => nextTick(scrollToRelevantHour),
);
</script>

<template>
    <div
        ref="scroller"
        class="max-h-[70vh] min-h-72 overflow-auto rounded-lg border bg-background"
    >
        <div :style="{ minWidth: isSingleDay ? undefined : '46rem' }">
            <div
                v-if="showDayHeader"
                class="sticky top-0 z-20 grid border-b bg-muted text-center text-xs text-muted-foreground"
                :style="{ gridTemplateColumns: columns }"
            >
                <div />
                <div
                    v-for="day in days"
                    :key="day.toISOString()"
                    class="border-l py-2"
                    :class="isToday(day) ? 'font-semibold text-primary' : ''"
                >
                    <span class="capitalize">{{ weekdayShort(day) }}</span>
                    <span
                        class="ml-1 inline-flex size-6 items-center justify-center rounded-full tabular-nums"
                        :class="
                            isToday(day)
                                ? 'bg-primary text-primary-foreground'
                                : ''
                        "
                        >{{ day.getDate() }}</span
                    >
                </div>
            </div>

            <div
                class="grid"
                :style="{
                    gridTemplateColumns: columns,
                    paddingTop: `${TOP_PADDING}px`,
                }"
            >
                <div>
                    <div
                        v-for="hour in hours"
                        :key="hour"
                        class="relative"
                        :style="{ height: `${HOUR_HEIGHT}px` }"
                    >
                        <span
                            class="absolute -top-2 right-2 text-[11px] whitespace-nowrap text-muted-foreground tabular-nums"
                        >
                            {{ formatClock(hour, 0) }}
                        </span>
                    </div>
                </div>

                <div
                    v-for="(day, dayIndex) in days"
                    :key="day.toISOString()"
                    class="relative border-l"
                    :class="isToday(day) ? 'bg-primary/5' : ''"
                    :style="{ height: `${hours.length * HOUR_HEIGHT}px` }"
                >
                    <div
                        v-for="hour in hours"
                        :key="hour"
                        class="relative border-t"
                        :style="{ height: `${HOUR_HEIGHT}px` }"
                    >
                        <div
                            class="absolute inset-x-0 top-1/2 border-t border-dashed border-border/60"
                        />
                    </div>

                    <div
                        v-if="isToday(day) && nowOffset !== null"
                        class="pointer-events-none absolute inset-x-0 z-10 border-t-2 border-red-500"
                        :style="{ top: `${nowOffset}px` }"
                    >
                        <span
                            class="absolute -top-1.5 -left-1 size-2.5 rounded-full bg-red-500"
                        />
                    </div>

                    <p
                        v-if="isSingleDay && !hasAppointments"
                        class="absolute inset-x-0 top-6 text-center text-sm text-muted-foreground"
                    >
                        Sin citas para este día.
                    </p>

                    <button
                        v-for="entry in positionedByDay[dayIndex]"
                        :key="entry.appointment.id"
                        type="button"
                        class="absolute z-10 overflow-hidden rounded-md border-l-4 px-2 py-1 text-left text-xs leading-snug shadow-xs transition hover:brightness-95 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        :class="[
                            appointmentTones[entry.appointment.status.value],
                            isSingleDay ? 'truncate' : '',
                        ]"
                        :style="entry.style"
                        @click="emit('select', entry.appointment)"
                    >
                        <template v-if="isSingleDay">
                            <span class="font-semibold tabular-nums">{{
                                entry.time
                            }}</span>
                            <span class="mx-1.5 opacity-50">·</span>
                            <span class="font-medium">{{
                                title(entry.appointment)
                            }}</span>
                            <span class="ml-1.5 opacity-70"
                                >— {{ entry.appointment.reason }}</span
                            >
                        </template>
                        <template v-else>
                            <span
                                class="block truncate font-semibold tabular-nums"
                                >{{ entry.time }}</span
                            >
                            <span class="block truncate">{{
                                title(entry.appointment)
                            }}</span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
