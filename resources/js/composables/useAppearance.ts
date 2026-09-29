import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
    toggleAppearance: () => void;
};

const DEFAULT_APPEARANCE: Appearance = 'light';

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    document.documentElement.classList.toggle('dark', value === 'dark');
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

/**
 * Read the saved preference. Anything other than "dark" (including the
 * former "system" option) falls back to light mode.
 */
const getStoredAppearance = (): Appearance => {
    if (typeof window === 'undefined') {
        return DEFAULT_APPEARANCE;
    }

    return localStorage.getItem('appearance') === 'dark' ? 'dark' : 'light';
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    updateTheme(getStoredAppearance());
}

const appearance = ref<Appearance>(DEFAULT_APPEARANCE);

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        appearance.value = getStoredAppearance();
    });

    const resolvedAppearance = computed<ResolvedAppearance>(
        () => appearance.value,
    );

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie so the server renders the right theme on first paint...
        setCookie('appearance', value);

        updateTheme(value);
    }

    function toggleAppearance() {
        updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
        toggleAppearance,
    };
}
