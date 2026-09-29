<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/format';
import auditLogRoutes from '@/routes/audit-logs';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type { Option, Paginated } from '@/types/models';

type AuditLogEntry = {
    id: number;
    action: Option;
    description: string;
    user: { name: string; role: string } | null;
    patient: { id: number; full_name: string } | null;
    ip_address: string | null;
    created_at: string;
};

const props = defineProps<{
    logs: Paginated<AuditLogEntry>;
    filters: { action: string; patient_id: number | null };
    patient: { id: number; full_name: string } | null;
    actions: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auditoría', href: auditLogRoutes.index() },
];

function applyFilters(changes: Partial<typeof props.filters>) {
    const query = Object.fromEntries(
        Object.entries({ ...props.filters, ...changes }).filter(
            ([, value]) => value !== '' && value !== null,
        ),
    );

    router.get(auditLogRoutes.index().url, query, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Auditoría" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Auditoría de accesos
                </h1>
                <p class="text-sm text-muted-foreground">
                    Registro de quién consultó o modificó información clínica.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    size="sm"
                    :variant="filters.action === '' ? 'default' : 'outline'"
                    @click="applyFilters({ action: '' })"
                >
                    Todas
                </Button>
                <Button
                    v-for="action in actions"
                    :key="action.value"
                    size="sm"
                    :variant="
                        filters.action === action.value ? 'default' : 'outline'
                    "
                    @click="applyFilters({ action: action.value })"
                >
                    {{ action.label }}
                </Button>
                <span
                    v-if="patient"
                    class="inline-flex items-center gap-1 rounded-full border bg-muted px-3 py-1 text-sm"
                >
                    Paciente: {{ patient.full_name }}
                    <button
                        type="button"
                        aria-label="Quitar filtro de paciente"
                        @click="applyFilters({ patient_id: null })"
                    >
                        <X class="size-3.5" />
                    </button>
                </span>
            </div>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Fecha</th>
                            <th class="px-4 py-3 font-medium">Usuario</th>
                            <th class="px-4 py-3 font-medium">Acción</th>
                            <th class="px-4 py-3 font-medium">Paciente</th>
                            <th class="px-4 py-3 font-medium">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="props.logs.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No hay registros.
                            </td>
                        </tr>
                        <tr v-for="log in props.logs.data" :key="log.id">
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDateTime(log.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ log.user?.name ?? 'Usuario eliminado' }}
                                <p class="text-xs text-muted-foreground">
                                    {{ log.user?.role }}
                                </p>
                            </td>
                            <td class="px-4 py-3">{{ log.description }}</td>
                            <td class="px-4 py-3">
                                <template v-if="log.patient">
                                    <Link
                                        :href="
                                            patientRoutes.show(log.patient.id)
                                        "
                                        class="font-medium hover:underline"
                                    >
                                        {{ log.patient.full_name }}
                                    </Link>
                                    <button
                                        v-if="
                                            filters.patient_id !==
                                            log.patient.id
                                        "
                                        type="button"
                                        class="ml-2 text-xs text-muted-foreground hover:underline"
                                        @click="
                                            applyFilters({
                                                patient_id: log.patient.id,
                                            })
                                        "
                                    >
                                        filtrar
                                    </button>
                                </template>
                                <span v-else>—</span>
                            </td>
                            <td
                                class="px-4 py-3 text-xs text-muted-foreground tabular-nums"
                            >
                                {{ log.ip_address ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="props.logs" />
        </div>
    </AppLayout>
</template>
