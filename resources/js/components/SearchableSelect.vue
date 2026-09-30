<script setup lang="ts">
import { onClickOutside, useDebounceFn } from '@vueuse/core';
import { Check, ChevronsUpDown, Search } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { computed, nextTick, ref, watch } from 'vue';
import { cn } from '@/lib/utils';

type OptionValue = string | number;

type SelectOption = {
    value: OptionValue;
    label: string;
};

const props = withDefaults(
    defineProps<{
        options: SelectOption[];
        modelValue?: OptionValue | null;
        defaultValue?: OptionValue | null;
        name?: string;
        id?: string;
        placeholder?: string;
        searchPlaceholder?: string;
        required?: boolean;
        disabled?: boolean;
        class?: HTMLAttributes['class'];
        searchUrl?: string;
        selectedLabel?: string | null;
    }>(),
    {
        placeholder: 'Selecciona una opción',
        searchPlaceholder: 'Buscar…',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: OptionValue): void;
    (e: 'change', value: OptionValue): void;
}>();

const value = ref<OptionValue>(props.modelValue ?? props.defaultValue ?? '');
const open = ref(false);
const query = ref('');
const activeIndex = ref(0);
const root = ref<HTMLElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);
const list = ref<HTMLElement | null>(null);
const remoteOptions = ref<SelectOption[]>([]);
const searching = ref(false);
const knownLabel = ref<string | null>(props.selectedLabel ?? null);
let searchRequest = 0;

const REMOTE_MIN_LENGTH = 2;

watch(
    () => props.modelValue,
    (next) => {
        value.value = next ?? '';
    },
);

const normalize = (text: string) =>
    text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

const selected = computed<SelectOption | undefined>(() => {
    const match = [...props.options, ...remoteOptions.value].find(
        (option) => String(option.value) === String(value.value),
    );

    if (match) {
        return match;
    }

    return value.value !== '' && knownLabel.value
        ? { value: value.value, label: knownLabel.value }
        : undefined;
});

const filtered = computed(() => {
    if (props.searchUrl) {
        return [...props.options, ...remoteOptions.value];
    }

    const terms = normalize(query.value).split(/\s+/).filter(Boolean);

    return props.options.filter((option) => {
        const label = normalize(option.label);

        return terms.every((term) => label.includes(term));
    });
});

const searchRemote = useDebounceFn(async (term: string) => {
    const current = ++searchRequest;

    try {
        const response = await fetch(
            `${props.searchUrl}?q=${encodeURIComponent(term)}`,
            {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            },
        );
        const results = response.ok
            ? ((await response.json()) as SelectOption[])
            : [];

        if (current === searchRequest) {
            remoteOptions.value = results;
        }
    } finally {
        if (current === searchRequest) {
            searching.value = false;
        }
    }
}, 250);

watch(query, (term) => {
    activeIndex.value = 0;

    if (!props.searchUrl) {
        return;
    }

    if (term.trim().length < REMOTE_MIN_LENGTH) {
        searchRequest++;
        remoteOptions.value = [];
        searching.value = false;

        return;
    }

    searching.value = true;
    searchRemote(term.trim());
});

const emptyMessage = computed(() => {
    if (!props.searchUrl) {
        return 'Sin resultados.';
    }

    if (query.value.trim().length < REMOTE_MIN_LENGTH) {
        return 'Escribe al menos dos caracteres para buscar.';
    }

    return searching.value ? 'Buscando…' : 'Sin resultados.';
});

function toggle() {
    if (props.disabled) {
        return;
    }

    if (open.value) {
        close();
    } else {
        openList();
    }
}

async function openList() {
    open.value = true;
    query.value = '';
    activeIndex.value = Math.max(
        filtered.value.findIndex(
            (option) => String(option.value) === String(value.value),
        ),
        0,
    );

    await nextTick();
    searchInput.value?.focus();
    scrollToActive();
}

function close() {
    open.value = false;
}

function choose(option: SelectOption) {
    value.value = option.value;
    knownLabel.value = option.label;
    emit('update:modelValue', option.value);
    emit('change', option.value);
    close();
}

function scrollToActive() {
    list.value
        ?.querySelector<HTMLElement>(`[data-index="${activeIndex.value}"]`)
        ?.scrollIntoView({ block: 'nearest' });
}

function move(step: number) {
    const total = filtered.value.length;

    if (total === 0) {
        return;
    }

    activeIndex.value = (activeIndex.value + step + total) % total;
    nextTick(scrollToActive);
}

function onSearchKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        move(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        move(-1);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const option = filtered.value[activeIndex.value];

        if (option) {
            choose(option);
        }
    } else if (event.key === 'Escape' || event.key === 'Tab') {
        close();
    }
}

onClickOutside(root, close);
</script>

<template>
    <div ref="root" :class="cn('relative w-full', props.class)">
        <input
            v-if="name"
            :name="name"
            :value="value"
            :required="required"
            tabindex="-1"
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 bottom-0 h-px opacity-0"
            @invalid.prevent="toggle"
        />
        <button
            :id="id"
            type="button"
            role="combobox"
            :aria-expanded="open"
            :disabled="disabled"
            :class="
                cn(
                    'flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-1 text-left text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm dark:bg-input/30',
                    'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
                )
            "
            @click="toggle"
            @keydown.down.prevent="openList"
        >
            <span
                class="truncate"
                :class="{ 'text-muted-foreground': !selected }"
            >
                {{ selected?.label ?? placeholder }}
            </span>
            <ChevronsUpDown class="size-4 shrink-0 opacity-50" />
        </button>

        <div
            v-if="open"
            class="absolute z-50 mt-1 w-full min-w-56 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md"
        >
            <div class="flex items-center gap-2 border-b px-3">
                <Search class="size-4 shrink-0 opacity-50" />
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    autocomplete="off"
                    :placeholder="searchPlaceholder"
                    class="h-9 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    @keydown="onSearchKeydown"
                />
            </div>
            <ul ref="list" role="listbox" class="max-h-60 overflow-y-auto p-1">
                <li
                    v-if="filtered.length === 0"
                    class="px-2 py-6 text-center text-sm text-muted-foreground"
                >
                    {{ emptyMessage }}
                </li>
                <li
                    v-for="(option, index) in filtered"
                    :key="option.value"
                    :data-index="index"
                    role="option"
                    :aria-selected="String(option.value) === String(value)"
                    :class="
                        cn(
                            'flex cursor-pointer items-center justify-between gap-2 rounded-sm px-2 py-1.5 text-sm',
                            index === activeIndex &&
                                'bg-accent text-accent-foreground',
                        )
                    "
                    @mouseenter="activeIndex = index"
                    @click="choose(option)"
                >
                    <span class="truncate">{{ option.label }}</span>
                    <Check
                        v-if="String(option.value) === String(value)"
                        class="size-4 shrink-0"
                    />
                </li>
            </ul>
        </div>
    </div>
</template>
