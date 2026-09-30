<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import AppointmentActions from '@/components/appointments/AppointmentActions.vue';
import CalendarMonthGrid from '@/components/appointments/CalendarMonthGrid.vue';
import CalendarTimeGrid from '@/components/appointments/CalendarTimeGrid.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    addDays,
    addMonths,
    formatLongDay,
    formatMonthTitle,
    formatWeekRange,
    MONTH_GRID_DAYS,
    monthGridStart,
    startOfDay,
    toISODate,
    weekDays,
} from '@/lib/calendar';
import { formatClock, formatDateTime } from '@/lib/format';
import appointmentRoutes from '@/routes/appointments';
import doctorRoutes from '@/routes/doctors';
import patientRoutes from '@/routes/patients';
import type { Appointment } from '@/types/models';

type CalendarView = 'day' | 'week' | 'month' | 'agenda';

const props = defineProps<{ role: string }>();

const isDesktop = useMediaQuery('(min-width: 1024px)');

const selected = ref(startOfDay(new Date()));
const desktopView = ref<CalendarView>('day');
const mobileView = ref<CalendarView>('day');
const view = computed<CalendarView>({
    get: () => (isDesktop.value ? desktopView.value : mobileView.value),
    set: (value) => {
        if (isDesktop.value) {
            desktopView.value = value;
        } else {
            mobileView.value = value;
        }
    },
});

const viewOptions = computed<{ value: CalendarView; label: string }[]>(() =>
    isDesktop.value
        ? [
              { value: 'day', label: 'Día' },
              { value: 'week', label: 'Semana' },
              { value: 'month', label: 'Mes' },
          ]
        : [
              { value: 'day', label: 'Día' },
              { value: 'agenda', label: 'Agenda' },
          ],
);

const page = usePage();
const ownDoctorId = computed(() =>
    props.role === 'doctor' ? page.props.auth.doctorId : null,
);
const workingHours = ref<Record<
    string,
    { start: string; end: string }[]
> | null>(null);

async function loadWorkingHours(from: string, to: string) {
    if (!ownDoctorId.value) {
        return;
    }

    try {
        const response = await fetch(
            doctorRoutes.availability(ownDoctorId.value, {
                query: { from, to },
            }).url,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        if (response.ok) {
            const payload = (await response.json()) as {
                days: Record<
                    string,
                    { blocks: { start: string; end: string }[] }
                >;
            };

            workingHours.value = Object.fromEntries(
                Object.entries(payload.days).map(([date, day]) => [
                    date,
                    day.blocks,
                ]),
            );
        }
    } catch {
        workingHours.value = null;
    }
}

const appointments = ref<Appointment[]>([]);
const loading = ref(false);
const loadFailed = ref(false);
let loadedKey = '';
let requestId = 0;

async function load(force = false) {
    const start = monthGridStart(selected.value);
    const key = toISODate(start);

    if (!force && key === loadedKey) {
        return;
    }

    const currentRequest = ++requestId;
    loading.value = true;
    loadFailed.value = false;

    try {
        const url = appointmentRoutes.calendar({
            query: {
                from: toISODate(start),
                to: toISODate(addDays(start, MONTH_GRID_DAYS - 1)),
            },
        }).url;
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        const payload = (await response.json()) as { data: Appointment[] };

        void loadWorkingHours(
            toISODate(start),
            toISODate(addDays(start, MONTH_GRID_DAYS - 1)),
        );

        if (currentRequest === requestId) {
            appointments.value = payload.data;
            loadedKey = key;
        }
    } catch {
        if (currentRequest === requestId) {
            loadFailed.value = true;
        }
    } finally {
        if (currentRequest === requestId) {
            loading.value = false;
        }
    }
}

onMounted(() => load());
watch(selected, () => load());

const title = computed(() => {
    if (view.value === 'week') {
        return formatWeekRange(selected.value);
    }

    if (view.value === 'month' || view.value === 'agenda') {
        return formatMonthTitle(selected.value);
    }

    return formatLongDay(selected.value);
});

function step(direction: 1 | -1) {
    selected.value =
        view.value === 'day'
            ? addDays(selected.value, direction)
            : view.value === 'week'
              ? addDays(selected.value, 7 * direction)
              : addMonths(selected.value, direction);
}

function stepMonth(direction: 1 | -1) {
    selected.value = addMonths(selected.value, direction);
}

function goToday() {
    selected.value = startOfDay(new Date());
}

function pickDay(day: Date, nextView?: CalendarView) {
    selected.value = startOfDay(day);

    if (nextView) {
        view.value = nextView;
    }
}

const dayAppointments = computed(() =>
    appointments.value.filter(
        (appointment) =>
            toISODate(new Date(appointment.scheduled_at)) ===
            toISODate(selected.value),
    ),
);

const agendaGroups = computed(() => {
    const month = selected.value.getMonth();
    const groups = new Map<string, Appointment[]>();

    for (const appointment of appointments.value) {
        const date = new Date(appointment.scheduled_at);

        if (date.getMonth() !== month) {
            continue;
        }

        const key = toISODate(date);
        groups.set(key, [...(groups.get(key) ?? []), appointment]);
    }

    return [...groups.entries()]
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([key, items]) => ({
            key,
            label: formatLongDay(new Date(`${key}T00:00:00`)),
            items,
        }));
});

const detail = ref<Appointment | null>(null);
const detailOpen = computed({
    get: () => detail.value !== null,
    set: (open) => {
        if (!open) {
            detail.value = null;
        }
    },
});

function personLabel(appointment: Appointment): string {
    return props.role === 'patient'
        ? (appointment.doctor?.name ?? '')
        : (appointment.patient?.full_name ?? '');
}

function clock(appointment: Appointment): string {
    const date = new Date(appointment.scheduled_at);

    return formatClock(date.getHours(), date.getMinutes());
}

function onChanged() {
    detail.value = null;
    load(true);
}

defineExpose({ reload: () => load(true) });
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-1">
                <Button size="sm" variant="outline" @click="goToday">
                    Hoy
                </Button>
                <Button
                    size="icon-sm"
                    variant="outline"
                    aria-label="Anterior"
                    @click="isDesktop ? step(-1) : stepMonth(-1)"
                >
                    <ChevronLeft />
                </Button>
                <Button
                    size="icon-sm"
                    variant="outline"
                    aria-label="Siguiente"
                    @click="isDesktop ? step(1) : stepMonth(1)"
                >
                    <ChevronRight />
                </Button>
                <h2 class="ml-2 text-base font-semibold">
                    {{ isDesktop ? title : formatMonthTitle(selected) }}
                </h2>
            </div>

            <div
                class="inline-flex rounded-md border p-0.5"
                role="tablist"
                aria-label="Vista del calendario"
            >
                <Button
                    v-for="option in viewOptions"
                    :key="option.value"
                    size="sm"
                    role="tab"
                    :aria-selected="view === option.value"
                    :variant="view === option.value ? 'default' : 'ghost'"
                    @click="view = option.value"
                >
                    {{ option.label }}
                </Button>
            </div>
        </div>

        <p v-if="loadFailed" class="text-sm text-destructive">
            No se pudo cargar el calendario.
            <button class="underline" type="button" @click="load(true)">
                Reintentar
            </button>
        </p>

        <CalendarMonthGrid
            v-if="!isDesktop"
            compact
            :date="selected"
            :appointments="appointments"
            :role="role"
            @pick="(day) => pickDay(day)"
        />

        <div :class="loading ? 'opacity-60 transition-opacity' : ''">
            <template v-if="view === 'day'">
                <p
                    v-if="!isDesktop"
                    class="mb-2 text-sm font-medium text-muted-foreground"
                >
                    {{ formatLongDay(selected) }}
                </p>
                <CalendarTimeGrid
                    :days="[selected]"
                    :working-hours="workingHours"
                    :appointments="dayAppointments"
                    :role="role"
                    @select="detail = $event"
                />
            </template>

            <CalendarTimeGrid
                v-else-if="view === 'week'"
                show-day-header
                :working-hours="workingHours"
                :days="weekDays(selected)"
                :appointments="appointments"
                :role="role"
                @select="detail = $event"
            />

            <CalendarMonthGrid
                v-else-if="view === 'month'"
                :date="selected"
                :appointments="appointments"
                :role="role"
                @pick="(day) => pickDay(day, 'day')"
                @select="detail = $event"
            />

            <div v-else class="grid gap-4">
                <p
                    v-if="agendaGroups.length === 0"
                    class="rounded-lg border px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    No hay citas este mes.
                </p>
                <section v-for="group in agendaGroups" :key="group.key">
                    <h3 class="mb-1.5 text-sm font-semibold">
                        {{ group.label }}
                    </h3>
                    <ul class="divide-y rounded-lg border">
                        <li
                            v-for="appointment in group.items"
                            :key="appointment.id"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 px-3 py-2.5 text-left hover:bg-muted/40"
                                @click="detail = appointment"
                            >
                                <span
                                    class="w-20 shrink-0 text-sm font-medium tabular-nums"
                                    >{{ clock(appointment) }}</span
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm">{{
                                        personLabel(appointment)
                                    }}</span>
                                    <span
                                        class="block truncate text-xs text-muted-foreground"
                                        >{{ appointment.reason }}</span
                                    >
                                </span>
                                <StatusBadge :status="appointment.status" />
                            </button>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <Dialog v-model:open="detailOpen">
            <DialogContent v-if="detail" class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ detail.reason }}</DialogTitle>
                    <DialogDescription>
                        {{ formatDateTime(detail.scheduled_at) }}
                    </DialogDescription>
                </DialogHeader>

                <dl class="grid gap-2 text-sm">
                    <div
                        v-if="role !== 'patient'"
                        class="flex justify-between gap-4"
                    >
                        <dt class="text-muted-foreground">Paciente</dt>
                        <dd class="font-medium">
                            <Link
                                v-if="detail.patient"
                                :href="patientRoutes.show(detail.patient.id)"
                                class="hover:underline"
                                >{{ detail.patient.full_name }}</Link
                            >
                        </dd>
                    </div>
                    <div
                        v-if="role !== 'doctor'"
                        class="flex justify-between gap-4"
                    >
                        <dt class="text-muted-foreground">Médico</dt>
                        <dd class="text-right">
                            {{ detail.doctor?.name }}
                            <span class="block text-xs text-muted-foreground">{{
                                detail.doctor?.specialty
                            }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Estado</dt>
                        <dd><StatusBadge :status="detail.status" /></dd>
                    </div>
                    <div v-if="detail.notes" class="grid gap-1">
                        <dt class="text-muted-foreground">Notas</dt>
                        <dd>{{ detail.notes }}</dd>
                    </div>
                </dl>

                <AppointmentActions
                    :appointment="detail"
                    :role="role"
                    @changed="onChanged"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
