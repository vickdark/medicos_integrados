<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataSubjectRequestController from '@/actions/App/Http/Controllers/DataSubjectRequestController';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate } from '@/lib/format';
import dataRequestRoutes from '@/routes/data-requests';
import patientRoutes from '@/routes/patients';
import type { BreadcrumbItem } from '@/types';
import type { Option, Paginated, TableFilters } from '@/types/models';

type DataRequest = {
    id: number;
    type: Option;
    details: string;
    status: Option;
    is_overdue: boolean;
    due_at: string;
    response: string | null;
    responded_at: string | null;
    responded_by_name: string | null;
    created_at: string;
    patient: { id: number; full_name: string; document_number: string | null };
};

const props = defineProps<{
    requests: Paginated<DataRequest>;
    filters: TableFilters;
    statuses: Option[];
    pendingCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Solicitudes de datos', href: dataRequestRoutes.index() },
];

const { filters, hasActiveFilters, reset } = useTableFilters(
    () => dataRequestRoutes.index().url,
    { search: props.filters.search, status: props.filters.status },
);

const answeringId = ref<number | null>(null);
</script>

<template>
    <Head title="Solicitudes de datos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Solicitudes de datos personales
                </h1>
                <p class="text-sm text-muted-foreground">
                    Derechos de los pacientes (Ley 1581): consultas en 10 días
                    hábiles y reclamos en 15.
                    <strong v-if="pendingCount > 0"
                        >{{ pendingCount }} pendiente{{
                            pendingCount === 1 ? '' : 's'
                        }}.</strong
                    >
                </p>
            </div>

            <TableToolbar
                v-model:search="filters.search"
                placeholder="Buscar por paciente, documento o correo"
                :can-reset="hasActiveFilters"
                @reset="reset"
            >
                <template #filters>
                    <div class="grid gap-1">
                        <Label
                            for="filter-status"
                            class="text-xs text-muted-foreground"
                            >Estado</Label
                        >
                        <NativeSelect
                            id="filter-status"
                            v-model="filters.status"
                            class="w-36"
                        >
                            <option value="">Todos</option>
                            <option
                                v-for="status in statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </option>
                        </NativeSelect>
                    </div>
                </template>
            </TableToolbar>

            <p
                v-if="requests.data.length === 0"
                class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground"
            >
                No hay solicitudes.
            </p>

            <ul v-else class="space-y-3">
                <li
                    v-for="request in requests.data"
                    :key="request.id"
                    class="space-y-3 rounded-lg border p-4 text-sm"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div>
                            <Link
                                :href="patientRoutes.show(request.patient.id)"
                                class="font-medium hover:underline"
                            >
                                {{ request.patient.full_name }}
                            </Link>
                            <span class="text-muted-foreground">
                                · {{ request.type.label }}
                            </span>
                        </div>
                        <StatusBadge :status="request.status" />
                    </div>

                    <p>{{ request.details }}</p>

                    <p class="text-xs text-muted-foreground">
                        Solicitada el {{ formatDate(request.created_at) }} ·
                        Plazo: {{ formatDate(request.due_at) }}
                        <span
                            v-if="request.is_overdue"
                            class="font-medium text-destructive"
                            >(vencido)</span
                        >
                    </p>

                    <div v-if="request.response" class="rounded-md bg-muted p-3">
                        <p class="text-xs text-muted-foreground">
                            Respondida por {{ request.responded_by_name }} el
                            {{ formatDate(request.responded_at) }}
                        </p>
                        <p>{{ request.response }}</p>
                    </div>

                    <template v-else>
                        <Button
                            v-if="answeringId !== request.id"
                            variant="outline"
                            size="sm"
                            @click="answeringId = request.id"
                        >
                            Responder
                        </Button>

                        <Form
                            v-else
                            v-bind="
                                DataSubjectRequestController.answer.form(
                                    request.id,
                                )
                            "
                            class="space-y-3"
                            :options="{ preserveScroll: true }"
                            v-slot="{ errors, processing }"
                            @success="answeringId = null"
                        >
                            <div class="grid gap-2">
                                <Label :for="`status-${request.id}`"
                                    >Resultado</Label
                                >
                                <NativeSelect
                                    :id="`status-${request.id}`"
                                    name="status"
                                    class="w-48"
                                >
                                    <option value="resolved">Atendida</option>
                                    <option value="rejected">Rechazada</option>
                                </NativeSelect>
                                <InputError :message="errors.status" />
                            </div>
                            <div class="grid gap-2">
                                <Label :for="`response-${request.id}`"
                                    >Respuesta para el paciente</Label
                                >
                                <Textarea
                                    :id="`response-${request.id}`"
                                    name="response"
                                    rows="3"
                                />
                                <InputError :message="errors.response" />
                            </div>
                            <div class="flex gap-2">
                                <Button size="sm" :disabled="processing"
                                    >Enviar respuesta</Button
                                >
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    @click="answeringId = null"
                                    >Cancelar</Button
                                >
                            </div>
                        </Form>
                    </template>
                </li>
            </ul>

            <Pagination :paginator="requests" />
        </div>
    </AppLayout>
</template>
