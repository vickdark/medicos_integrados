<script setup lang="ts">
import { computed } from 'vue';

type Bar = {
    label: string;
    value: number;
    current?: boolean;
    today?: boolean;
};

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        data: Bar[];
        format?: (value: number) => string;
        /** Name of what each value counts, used by the table view and tooltips. */
        unit?: string;
    }>(),
    { format: (value: number) => String(value), unit: 'Valor' },
);

const PLOT_HEIGHT = 160;

/** Rounds the top of the scale to a clean 1 / 2 / 5 × 10ⁿ number. */
function niceMax(value: number): number {
    if (value <= 0) {
        return 1;
    }

    const magnitude = 10 ** Math.floor(Math.log10(value));
    const normalized = value / magnitude;
    const step =
        [1, 2, 5, 10].find((candidate) => normalized <= candidate) ?? 10;

    return step * magnitude;
}

const max = computed(() =>
    niceMax(Math.max(...props.data.map((bar) => bar.value), 0)),
);
const ticks = computed(() => [max.value, max.value / 2, 0]);
const hasData = computed(() => props.data.some((bar) => bar.value > 0));
const peak = computed(() => Math.max(...props.data.map((bar) => bar.value), 0));

const isHighlighted = (bar: Bar) => bar.current || bar.today;

const summary = computed(
    () =>
        `${props.title}: ` +
        props.data
            .map((bar) => `${bar.label} ${props.format(bar.value)}`)
            .join(', '),
);
</script>

<template>
    <figure class="flex flex-col gap-3 rounded-xl border bg-card p-4">
        <figcaption>
            <p class="text-sm font-semibold">{{ title }}</p>
            <p v-if="description" class="text-xs text-muted-foreground">
                {{ description }}
            </p>
        </figcaption>

        <p
            v-if="!hasData"
            class="flex items-center justify-center text-sm text-muted-foreground"
            :style="{ height: `${PLOT_HEIGHT + 24}px` }"
        >
            Aún no hay datos para mostrar.
        </p>

        <div v-else class="flex gap-2" role="img" :aria-label="summary">
            <div
                class="flex flex-col justify-between text-right text-[11px] text-muted-foreground tabular-nums"
                :style="{ height: `${PLOT_HEIGHT}px` }"
                aria-hidden="true"
            >
                <span v-for="tick in ticks" :key="tick" class="leading-none">
                    {{ format(tick) }}
                </span>
            </div>

            <div class="min-w-0 flex-1">
                <div class="relative" :style="{ height: `${PLOT_HEIGHT}px` }">
                    <div
                        v-for="tick in ticks"
                        :key="`grid-${tick}`"
                        class="absolute inset-x-0 border-t border-border"
                        :style="{ bottom: `${(tick / max) * 100}%` }"
                        aria-hidden="true"
                    />

                    <div
                        class="absolute inset-0 grid items-end gap-0.5"
                        :style="{
                            gridTemplateColumns: `repeat(${data.length}, minmax(0, 1fr))`,
                        }"
                    >
                        <div
                            v-for="bar in data"
                            :key="bar.label"
                            class="group relative flex h-full flex-col items-center justify-end outline-none"
                            tabindex="0"
                        >
                            <span
                                v-if="isHighlighted(bar) || bar.value === peak"
                                class="mb-1 text-[11px] leading-none font-medium tabular-nums"
                                aria-hidden="true"
                            >
                                {{ format(bar.value) }}
                            </span>
                            <div
                                class="w-full max-w-6 rounded-t-[4px] transition-opacity group-hover:opacity-80 group-focus:opacity-80"
                                :class="
                                    isHighlighted(bar)
                                        ? 'bg-brand-600 dark:bg-brand-400'
                                        : 'bg-brand-300 dark:bg-brand-800'
                                "
                                :style="{
                                    height: `${(bar.value / max) * 100}%`,
                                    minHeight: bar.value > 0 ? '2px' : '0',
                                }"
                            />
                            <div
                                class="pointer-events-none absolute bottom-full z-10 mb-1 hidden rounded-md bg-foreground px-2 py-1 text-xs whitespace-nowrap text-background shadow group-hover:block group-focus:block"
                                :style="{
                                    bottom: `${(bar.value / max) * 100}%`,
                                }"
                                role="tooltip"
                            >
                                {{ bar.label }} · {{ format(bar.value) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-1.5 grid gap-0.5 text-[11px] text-muted-foreground"
                    :style="{
                        gridTemplateColumns: `repeat(${data.length}, minmax(0, 1fr))`,
                    }"
                    aria-hidden="true"
                >
                    <span
                        v-for="bar in data"
                        :key="`label-${bar.label}`"
                        class="truncate text-center capitalize"
                        :class="
                            isHighlighted(bar)
                                ? 'font-semibold text-foreground'
                                : ''
                        "
                    >
                        {{ bar.label }}
                    </span>
                </div>
            </div>
        </div>

        <table class="sr-only">
            <caption>
                {{
                    title
                }}
            </caption>
            <thead>
                <tr>
                    <th scope="col">Período</th>
                    <th scope="col">{{ unit }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="bar in data" :key="`row-${bar.label}`">
                    <th scope="row">{{ bar.label }}</th>
                    <td>{{ format(bar.value) }}</td>
                </tr>
            </tbody>
        </table>
    </figure>
</template>
