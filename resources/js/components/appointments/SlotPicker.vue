<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    addDays,
    addMonths,
    formatLongDay,
    formatMonthTitle,
    isSameDay,
    monthGridDays,
    monthGridStart,
    MONTH_GRID_DAYS,
    startOfDay,
    toISODate,
    weekdayShort,
} from '@/lib/calendar';
import { formatClock } from '@/lib/format';
import doctorRoutes from '@/routes/doctors';

type DayAvailability = {
    blocks: { start: string; end: string }[];
    booked: string[];
};

type Availability = {
    slot_minutes: number;
    has_schedule: boolean;
    days: Record<string, DayAvailability>;
};

type SlotState =
    'available' | 'selected' | 'booked' | 'off' | 'past' | 'current';

const props = defineProps<{
    doctorId: number | null;
    ignoreAppointmentId?: number;
    currentAt?: string;
    initialDate?: string;
}>();

const selected = defineModel<string>({ default: '' });

function startingDay(): Date {
    const initial = props.initialDate
        ? startOfDay(new Date(props.initialDate))
        : null;

    return initial && initial.getTime() >= startOfDay(new Date()).getTime()
        ? initial
        : startOfDay(new Date());
}

const focusDate = ref(startingDay());
const availability = ref<Availability | null>(null);
const loading = ref(false);
const loadFailed = ref(false);
let requestId = 0;

const today = computed(() => startOfDay(new Date()));
const gridStart = computed(() => monthGridStart(focusDate.value));

async function load() {
    if (!props.doctorId) {
        availability.value = null;

        return;
    }

    const current = ++requestId;
    loading.value = true;
    loadFailed.value = false;

    try {
        const query: Record<string, string | number> = {
            from: toISODate(gridStart.value),
            to: toISODate(addDays(gridStart.value, MONTH_GRID_DAYS - 1)),
        };

        if (props.ignoreAppointmentId) {
            query.exclude = props.ignoreAppointmentId;
        }

        const response = await fetch(
            doctorRoutes.availability(props.doctorId, { query }).url,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        if (current === requestId) {
            availability.value = (await response.json()) as Availability;
        }
    } catch {
        if (current === requestId) {
            loadFailed.value = true;
        }
    } finally {
        if (current === requestId) {
            loading.value = false;
        }
    }
}

watch(
    () => [props.doctorId, gridStart.value.getTime()],
    () => load(),
    { immediate: true },
);

watch(
    () => props.doctorId,
    () => {
        selected.value = '';
    },
);

const toMinutes = (time: string): number => {
    const [hours, minutes] = time.split(':').map(Number);

    return hours * 60 + minutes;
};

const slotMinutes = computed(() => availability.value?.slot_minutes ?? 30);

function slotValue(day: Date, minute: number): string {
    const hours = String(Math.floor(minute / 60)).padStart(2, '0');
    const minutes = String(minute % 60).padStart(2, '0');

    return `${toISODate(day)}T${hours}:${minutes}`;
}

const currentValue = computed(() => {
    if (!props.currentAt) {
        return '';
    }

    const date = new Date(props.currentAt);

    return slotValue(date, date.getHours() * 60 + date.getMinutes());
});

function stateOf(day: Date, minute: number): SlotState {
    const info = availability.value?.days[toISODate(day)];

    const insideHours = (info?.blocks ?? []).some(
        (block) =>
            minute >= toMinutes(block.start) &&
            minute + slotMinutes.value <= toMinutes(block.end),
    );

    if (!insideHours) {
        return 'off';
    }

    const value = slotValue(day, minute);

    if (new Date(value).getTime() <= Date.now()) {
        return 'past';
    }

    if (value === currentValue.value) {
        return 'current';
    }

    if (
        (info?.booked ?? []).some(
            (time) => Math.abs(toMinutes(time) - minute) < slotMinutes.value,
        )
    ) {
        return 'booked';
    }

    return value === selected.value ? 'selected' : 'available';
}

function slotsOf(day: Date): number[] {
    const blocks = availability.value?.days[toISODate(day)]?.blocks ?? [];

    if (blocks.length === 0) {
        return [];
    }

    const start = Math.min(...blocks.map((block) => toMinutes(block.start)));
    const end = Math.max(...blocks.map((block) => toMinutes(block.end)));
    const result: number[] = [];

    for (
        let minute = start;
        minute + slotMinutes.value <= end;
        minute += slotMinutes.value
    ) {
        result.push(minute);
    }

    return result;
}

const daySlots = computed(() =>
    slotsOf(focusDate.value).map((minute) => ({
        minute,
        state: stateOf(focusDate.value, minute),
    })),
);

const freeCountByDay = computed(() => {
    const counts = new Map<string, number>();

    for (const day of monthGridDays(focusDate.value)) {
        counts.set(
            toISODate(day),
            slotsOf(day).filter((minute) =>
                ['available', 'selected'].includes(stateOf(day, minute)),
            ).length,
        );
    }

    return counts;
});

const freeCount = (day: Date): number =>
    freeCountByDay.value.get(toISODate(day)) ?? 0;

const hasHours = (day: Date): boolean =>
    (availability.value?.days[toISODate(day)]?.blocks.length ?? 0) > 0;

const days = computed(() => monthGridDays(focusDate.value));
const weekdayLabels = computed(() =>
    days.value.slice(0, 7).map((day) => weekdayShort(day)),
);

const isOutside = (day: Date) => day.getMonth() !== focusDate.value.getMonth();
const isPast = (day: Date) => day.getTime() < today.value.getTime();

function pickDay(day: Date) {
    if (!isPast(day)) {
        focusDate.value = startOfDay(day);
    }
}

const canGoBack = computed(
    () =>
        addMonths(focusDate.value, -1).getTime() >=
        new Date(
            today.value.getFullYear(),
            today.value.getMonth(),
            1,
        ).getTime() -
            1,
);

function stepMonth(direction: 1 | -1) {
    const target = addMonths(focusDate.value, direction);
    const firstOfTarget = new Date(target.getFullYear(), target.getMonth(), 1);

    focusDate.value =
        direction === -1 && firstOfTarget.getTime() <= today.value.getTime()
            ? today.value
            : firstOfTarget;
}

function goToday() {
    focusDate.value = startOfDay(new Date());
}

function pickSlot(minute: number) {
    if (['available', 'selected'].includes(stateOf(focusDate.value, minute))) {
        selected.value = slotValue(focusDate.value, minute);
    }
}

const labels: Record<SlotState, string> = {
    available: 'Disponible',
    selected: 'Elegida',
    booked: 'Ocupado',
    off: 'Fuera de horario',
    past: 'Ya pasó',
    current: 'Cita actual',
};

const tones: Record<SlotState, string> = {
    available:
        'border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20',
    selected: 'border-primary bg-primary text-primary-foreground',
    booked: 'border-transparent bg-neutral-200 text-neutral-500 dark:bg-neutral-700/50 dark:text-neutral-400',
    off: 'border-transparent bg-[repeating-linear-gradient(45deg,transparent,transparent_4px,var(--border)_4px,var(--border)_5px)] text-muted-foreground/70',
    past: 'border-transparent bg-muted/40 text-muted-foreground/60',
    current:
        'border-amber-400 bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
};

const selectedLabel = computed(() => {
    if (!selected.value) {
        return '';
    }

    const date = new Date(selected.value);

    return `${formatLongDay(date)}, ${formatClock(date.getHours(), date.getMinutes())}`;
});

const isToday = (day: Date) => isSameDay(day, new Date());
const isSelectedDay = (day: Date) => isSameDay(day, focusDate.value);
</script>

<template>
    <div class="grid gap-3">
        <p
            v-if="!doctorId"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            Elige un médico para ver su disponibilidad.
        </p>

        <template v-else>
            <p v-if="loadFailed" class="text-sm text-destructive">
                No se pudo cargar la disponibilidad.
                <button type="button" class="underline" @click="load">
                    Reintentar
                </button>
            </p>

            <div
                class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start"
                :class="loading ? 'opacity-70 transition-opacity' : ''"
            >
                <div class="rounded-lg border">
                    <div
                        class="flex items-center justify-between gap-2 border-b p-3"
                    >
                        <div class="flex items-center gap-1">
                            <Button
                                type="button"
                                size="icon-sm"
                                variant="outline"
                                aria-label="Mes anterior"
                                :disabled="!canGoBack"
                                @click="stepMonth(-1)"
                            >
                                <ChevronLeft />
                            </Button>
                            <Button
                                type="button"
                                size="icon-sm"
                                variant="outline"
                                aria-label="Mes siguiente"
                                @click="stepMonth(1)"
                            >
                                <ChevronRight />
                            </Button>
                            <span class="ml-2 text-sm font-semibold">{{
                                formatMonthTitle(focusDate)
                            }}</span>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="goToday"
                        >
                            Hoy
                        </Button>
                    </div>

                    <div
                        class="grid grid-cols-7 border-b bg-muted/50 py-2 text-center text-xs text-muted-foreground"
                    >
                        <span
                            v-for="label in weekdayLabels"
                            :key="label"
                            class="capitalize"
                            >{{ label }}</span
                        >
                    </div>

                    <div class="grid grid-cols-7">
                        <button
                            v-for="day in days"
                            :key="day.toISOString()"
                            type="button"
                            class="flex h-14 flex-col items-center justify-center gap-0.5 border-t border-l text-sm tabular-nums transition-colors [&:nth-child(7n+1)]:border-l-0"
                            :class="[
                                isSelectedDay(day)
                                    ? 'bg-primary text-primary-foreground'
                                    : isPast(day) ||
                                        !hasHours(day) ||
                                        freeCount(day) === 0
                                      ? 'cursor-not-allowed text-muted-foreground/50'
                                      : 'hover:bg-emerald-50 dark:hover:bg-emerald-500/10',
                                isOutside(day) && !isSelectedDay(day)
                                    ? 'bg-muted/30'
                                    : '',
                                isToday(day) && !isSelectedDay(day)
                                    ? 'font-semibold text-primary'
                                    : '',
                            ]"
                            :disabled="isPast(day)"
                            :aria-label="
                                day.toLocaleDateString('es', {
                                    dateStyle: 'full',
                                })
                            "
                            @click="pickDay(day)"
                        >
                            {{ day.getDate() }}
                            <span
                                class="text-[10px] leading-none"
                                :class="
                                    isSelectedDay(day)
                                        ? 'text-primary-foreground/80'
                                        : freeCount(day) > 0
                                          ? 'text-emerald-600 dark:text-emerald-400'
                                          : 'text-muted-foreground/60'
                                "
                            >
                                <template v-if="isPast(day) || !hasHours(day)">
                                    &nbsp;
                                </template>
                                <template v-else-if="freeCount(day) > 0">
                                    {{ freeCount(day) }} libres
                                </template>
                                <template v-else>Lleno</template>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="rounded-lg border">
                    <div class="border-b p-3">
                        <p class="text-sm font-semibold">
                            Horarios de atención
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatLongDay(focusDate) }} · citas de
                            {{ slotMinutes }} min
                        </p>
                    </div>

                    <p
                        v-if="availability && daySlots.length === 0"
                        class="p-6 text-center text-sm text-muted-foreground"
                    >
                        El médico no atiende este día. Elige otro en el
                        calendario.
                    </p>

                    <ul
                        v-else
                        class="grid max-h-[26rem] gap-1.5 overflow-y-auto p-2"
                    >
                        <li v-for="slot in daySlots" :key="slot.minute">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm transition-colors"
                                :class="tones[slot.state]"
                                :disabled="
                                    !['available', 'selected'].includes(
                                        slot.state,
                                    )
                                "
                                :aria-pressed="slot.state === 'selected'"
                                @click="pickSlot(slot.minute)"
                            >
                                <span class="font-medium tabular-nums">
                                    {{
                                        formatClock(
                                            Math.floor(slot.minute / 60),
                                            slot.minute % 60,
                                        )
                                    }}
                                </span>
                                <span class="text-xs">{{
                                    labels[slot.state]
                                }}</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground"
            >
                <span class="inline-flex items-center gap-1.5">
                    <span
                        class="size-3 rounded border border-emerald-300 bg-emerald-50 dark:border-emerald-500/40 dark:bg-emerald-500/10"
                    />
                    Disponible
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span
                        class="size-3 rounded bg-neutral-200 dark:bg-neutral-700/50"
                    />
                    Ocupado
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span
                        class="size-3 rounded border bg-[repeating-linear-gradient(45deg,transparent,transparent_2px,var(--border)_2px,var(--border)_3px)]"
                    />
                    Fuera de horario
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="size-3 rounded bg-primary" />
                    Elegida
                </span>
            </div>

            <p v-if="selectedLabel" class="text-sm">
                Horario elegido: <strong>{{ selectedLabel }}</strong>
            </p>
        </template>
    </div>
</template>
