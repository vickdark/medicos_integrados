import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import pushSubscriptionRoutes from '@/routes/push-subscriptions';

const isSupported = () =>
    typeof window !== 'undefined' &&
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window;

function urlBase64ToUint8Array(base64: string): Uint8Array<ArrayBuffer> {
    const padded = base64 + '='.repeat((4 - (base64.length % 4)) % 4);
    const raw = atob(padded.replace(/-/g, '+').replace(/_/g, '/'));
    const bytes = new Uint8Array(new ArrayBuffer(raw.length));

    for (let index = 0; index < raw.length; index++) {
        bytes[index] = raw.charCodeAt(index);
    }

    return bytes;
}

async function send(
    url: string,
    method: 'POST' | 'DELETE',
    body: unknown,
): Promise<void> {
    const token = decodeURIComponent(
        document.cookie
            .split('; ')
            .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': token,
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        throw new Error('request-failed');
    }
}

/**
 * Lets the signed-in user turn browser push notifications on or off for this device.
 */
export function usePushNotifications() {
    const page = usePage();
    const publicKey = computed(() => page.props.webPushPublicKey);

    const supported = isSupported();
    const permission = ref<NotificationPermission>(
        supported ? Notification.permission : 'denied',
    );
    const subscribed = ref(false);
    const ready = ref(!supported);
    const busy = ref(false);
    const error = ref<string | null>(null);

    async function registration() {
        return navigator.serviceWorker.register('/sw.js');
    }

    onMounted(async () => {
        if (!supported) {
            return;
        }

        const existing = await navigator.serviceWorker.getRegistration('/sw.js');
        const subscription = await existing?.pushManager.getSubscription();

        subscribed.value = Boolean(subscription);
        ready.value = true;
    });

    async function subscribe() {
        if (!supported || !publicKey.value) {
            return;
        }

        busy.value = true;
        error.value = null;

        try {
            permission.value = await Notification.requestPermission();

            if (permission.value !== 'granted') {
                error.value =
                    'El navegador bloqueó las notificaciones. Actívalas desde los permisos del sitio.';

                return;
            }

            const registered = await registration();
            await navigator.serviceWorker.ready;

            const subscription =
                (await registered.pushManager.getSubscription()) ??
                (await registered.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey.value),
                }));

            await send(
                pushSubscriptionRoutes.store().url,
                'POST',
                subscription.toJSON(),
            );

            subscribed.value = true;
        } catch {
            error.value = 'No pudimos activar las notificaciones. Inténtalo de nuevo.';
        } finally {
            busy.value = false;
        }
    }

    async function unsubscribe() {
        busy.value = true;
        error.value = null;

        try {
            const existing = await navigator.serviceWorker.getRegistration('/sw.js');
            const subscription = await existing?.pushManager.getSubscription();

            if (subscription) {
                await send(
                    pushSubscriptionRoutes.destroy().url,
                    'DELETE',
                    { endpoint: subscription.endpoint },
                );
                await subscription.unsubscribe();
            }

            subscribed.value = false;
        } catch {
            error.value = 'No pudimos desactivar las notificaciones. Inténtalo de nuevo.';
        } finally {
            busy.value = false;
        }
    }

    return {
        supported,
        configured: computed(() => Boolean(publicKey.value)),
        permission,
        subscribed,
        ready,
        busy,
        error,
        subscribe,
        unsubscribe,
    };
}
