import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive, watch } from 'vue';

export type TableFilterValue = string | number | null;

/**
 * Keeps a listing's filters in sync with the URL. Text search is debounced;
 * any other filter reloads the table immediately. Only the table data is
 * requested again, so the page keeps its state and scroll position.
 */
export function useTableFilters<T extends Record<string, TableFilterValue>>(
    url: () => string,
    initialFilters: T,
) {
    const filters = reactive({ ...initialFilters }) as T;

    const activeFilters = computed(() =>
        Object.fromEntries(
            Object.entries(filters).filter(
                ([, value]) =>
                    value !== '' && value !== null && value !== undefined,
            ),
        ),
    );

    const hasActiveFilters = computed(
        () => Object.keys(activeFilters.value).length > 0,
    );

    function visit() {
        router.get(url(), activeFilters.value, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    const debouncedVisit = useDebounceFn(visit, 300);

    watch(
        () => filters.search,
        () => debouncedVisit(),
    );

    watch(
        () =>
            JSON.stringify(
                Object.entries(filters).filter(([key]) => key !== 'search'),
            ),
        () => visit(),
    );

    function reset() {
        for (const key of Object.keys(filters) as (keyof T)[]) {
            filters[key] = (
                typeof initialFilters[key] === 'number' ? null : ''
            ) as T[keyof T];
        }
    }

    return { filters, activeFilters, hasActiveFilters, reset };
}
