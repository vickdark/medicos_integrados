import { router, usePage } from '@inertiajs/vue3';
import { useEventListener, useIntervalFn } from '@vueuse/core';
import { computed, onMounted, ref } from 'vue';
import { login, logout } from '@/routes';
import { keepAlive } from '@/routes/session';

const WARNING_SECONDS = 60;
const PING_EVERY_MS = 60_000;
const STORAGE_KEY = 'idle:last-activity';
const ACTIVITY_EVENTS = [
    'mousemove',
    'mousedown',
    'keydown',
    'scroll',
    'touchstart',
] as const;

function sharedActivity(): number {
    try {
        return Number(window.localStorage.getItem(STORAGE_KEY)) || 0;
    } catch {
        return 0;
    }
}

function shareActivity(at: number): void {
    try {
        window.localStorage.setItem(STORAGE_KEY, String(at));
    } catch {
        // Without storage the tabs just do not share their activity.
    }
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Warns the clinic staff and closes their session after a period without
 * activity. The server enforces the same limit, so this is the friendly side
 * of it: a countdown instead of an unexpected login page.
 */
export function useIdleLogout() {
    const page = usePage();
    const limitMs = computed(
        () => (page.props.auth?.idleTimeoutMinutes ?? 0) * 60_000,
    );
    const enabled = computed(() => limitMs.value > 0);

    const secondsLeft = ref(0);
    const warning = ref(false);

    let lastActivity = Date.now();
    let lastPing = Date.now();

    function ping(): void {
        lastPing = Date.now();

        fetch(keepAlive().url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-XSRF-TOKEN': xsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then((response) => {
            if (response.status === 401 || response.status === 419) {
                closeSession();
            }
        });
    }

    function closeSession(): void {
        router.post(
            logout(),
            {},
            {
                onFinish: () =>
                    window.location.assign(login({ query: { expired: 1 } }).url),
            },
        );
    }

    function markActivity(): void {
        if (!enabled.value || warning.value) {
            return;
        }

        lastActivity = Date.now();
        shareActivity(lastActivity);

        if (lastActivity - lastPing > PING_EVERY_MS) {
            ping();
        }
    }

    function stayConnected(): void {
        warning.value = false;
        lastActivity = Date.now();
        shareActivity(lastActivity);
        ping();
    }

    function check(): void {
        if (!enabled.value) {
            return;
        }

        const idleMs = Date.now() - Math.max(lastActivity, sharedActivity());

        if (idleMs >= limitMs.value) {
            closeSession();

            return;
        }

        const remaining = Math.ceil((limitMs.value - idleMs) / 1000);

        if (remaining <= WARNING_SECONDS) {
            warning.value = true;
            secondsLeft.value = remaining;
        } else if (warning.value) {
            warning.value = false;
        }
    }

    onMounted(() => shareActivity(Date.now()));

    ACTIVITY_EVENTS.forEach((event) =>
        useEventListener(window, event, markActivity, { passive: true }),
    );
    useIntervalFn(check, 1000);

    return { warning, secondsLeft, stayConnected, logoutNow: closeSession };
}
