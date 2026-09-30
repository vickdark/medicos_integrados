self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let payload;

    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'Médicos Integrados', body: event.data.text() };
    }

    const { title, ...options } = payload;

    event.waitUntil(
        self.registration.showNotification(title || 'Médicos Integrados', options),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = new URL(
        event.notification.data?.url || '/',
        self.location.origin,
    ).href;

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clients) => {
                const open = clients.find((client) => 'focus' in client);

                if (open) {
                    return open.navigate(target).then((client) => client?.focus());
                }

                return self.clients.openWindow(target);
            }),
    );
});
