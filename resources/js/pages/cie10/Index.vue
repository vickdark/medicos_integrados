<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    CircleAlert,
    Download,
    ExternalLink,
    FlaskConical,
    Upload,
} from 'lucide-vue-next';
import { computed } from 'vue';
import Cie10CatalogController from '@/actions/App/Http/Controllers/Cie10CatalogController';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/format';
import cie10Routes from '@/routes/cie10';
import type { BreadcrumbItem } from '@/types';
import type { Paginated, TableFilters } from '@/types/models';

type ImportCounts = {
    read: number;
    valid: number;
    rejected: number;
    inserted: number;
    updated: number;
    unchanged: number;
    deactivated: number;
};

type ImportRun = {
    id: number;
    file_name: string;
    dry_run: boolean;
    deactivate_missing: boolean;
    user: string | null;
    created_at: string;
    counts: ImportCounts;
    has_report: boolean;
};

const props = defineProps<{
    stats: { active: number; inactive: number; last_load_at: string | null };
    latest:
        | (ImportRun & {
              rejection_sample: {
                  line: string;
                  code: string;
                  reason: string;
              }[];
          })
        | null;
    imports: ImportRun[];
    codes: Paginated<{
        id: number;
        code: string;
        description: string;
        category: string | null;
        chapter: string | null;
        is_active: boolean;
    }>;
    filters: TableFilters;
    sourceUrl: string;
    maxFileMb: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Catálogo CIE-10', href: cie10Routes.index() },
];

const { filters, hasActiveFilters, reset } = useTableFilters(
    () => cie10Routes.index().url,
    { search: props.filters.search, status: props.filters.status },
);

const countLabels: { key: keyof ImportCounts; label: string }[] = [
    { key: 'read', label: 'Filas leídas' },
    { key: 'valid', label: 'Válidas' },
    { key: 'rejected', label: 'Rechazadas' },
    { key: 'inserted', label: 'Códigos nuevos' },
    { key: 'updated', label: 'Actualizados' },
    { key: 'unchanged', label: 'Sin cambios' },
    { key: 'deactivated', label: 'Deshabilitados' },
];

const statCards = computed(() => [
    {
        label: 'Códigos habilitados',
        value: props.stats.active.toLocaleString('es'),
    },
    {
        label: 'Códigos deshabilitados',
        value: props.stats.inactive.toLocaleString('es'),
    },
    {
        label: 'Última carga',
        value: props.stats.last_load_at
            ? formatDateTime(props.stats.last_load_at)
            : 'Aún no se ha cargado la tabla oficial',
    },
]);

const rejectionsUrl = (id: number) => cie10Routes.imports.rejections(id).url;
</script>

<template>
    <Head title="Catálogo CIE-10" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Catálogo CIE-10
                </h1>
                <p class="text-sm text-muted-foreground">
                    Carga y actualiza los códigos de diagnóstico desde la tabla
                    oficial de SISPRO (Ministerio de Salud).
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <Card v-for="card in statCards" :key="card.label" class="gap-2">
                    <CardHeader>
                        <CardDescription>{{ card.label }}</CardDescription>
                        <CardTitle class="text-2xl tabular-nums">
                            {{ card.value }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-5">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Cargar la tabla oficial</CardTitle>
                        <CardDescription>
                            Primero simula la carga para revisar el resultado;
                            luego ejecútala sin la simulación.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-5">
                        <ol
                            class="grid list-decimal gap-2 pl-5 text-sm text-muted-foreground"
                        >
                            <li>
                                Abre la tabla CIE-10 en SISPRO.
                                <a
                                    :href="sourceUrl"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1 font-medium text-primary hover:underline"
                                >
                                    Abrir SISPRO
                                    <ExternalLink class="size-3.5" />
                                </a>
                            </li>
                            <li>
                                Escribe tu correo en «Email para envío de datos
                                exportados» y exporta. El archivo llega a tu
                                correo.
                            </li>
                            <li>
                                Súbelo aquí en Excel o CSV (máximo
                                {{ maxFileMb }} MB).
                            </li>
                        </ol>

                        <Form
                            v-bind="Cie10CatalogController.store.form()"
                            class="grid gap-4"
                            reset-on-success
                            v-slot="{ errors, processing, progress }"
                        >
                            <div class="grid gap-2">
                                <Label for="cie10-file">Archivo *</Label>
                                <Input
                                    id="cie10-file"
                                    type="file"
                                    name="file"
                                    accept=".xlsx,.xls,.ods,.csv,.txt"
                                    required
                                />
                                <InputError :message="errors.file" />
                            </div>

                            <Label
                                for="cie10-dry-run"
                                class="flex items-start gap-3 font-normal"
                            >
                                <Checkbox
                                    id="cie10-dry-run"
                                    name="dry_run"
                                    value="1"
                                    :default-value="true"
                                    class="mt-0.5"
                                />
                                <span>
                                    <strong>Solo simular.</strong>
                                    Muestra qué pasaría sin guardar cambios.
                                </span>
                            </Label>

                            <Label
                                for="cie10-deactivate"
                                class="flex items-start gap-3 font-normal"
                            >
                                <Checkbox
                                    id="cie10-deactivate"
                                    name="deactivate_missing"
                                    value="1"
                                    class="mt-0.5"
                                />
                                <span>
                                    <strong
                                        >Deshabilitar los códigos que ya no
                                        vienen en la tabla.</strong
                                    >
                                    Úsalo solo con la tabla completa. No se
                                    borran: siguen visibles en las consultas que
                                    los usan.
                                </span>
                            </Label>

                            <Button :disabled="processing" class="w-fit">
                                <Spinner v-if="processing" />
                                <Upload v-else />
                                {{
                                    processing
                                        ? 'Procesando…'
                                        : 'Procesar archivo'
                                }}
                            </Button>
                            <p
                                v-if="processing"
                                class="text-xs text-muted-foreground"
                            >
                                <template
                                    v-if="
                                        progress && progress.percentage! < 100
                                    "
                                >
                                    Subiendo el archivo:
                                    {{ progress.percentage }} %
                                </template>
                                <template v-else>
                                    Procesando la tabla; con la tabla completa
                                    puede tardar alrededor de un minuto.
                                </template>
                            </p>
                        </Form>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-3">
                    <CardHeader>
                        <CardTitle class="flex flex-wrap items-center gap-2">
                            Resultado
                            <span
                                v-if="latest"
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    latest.dry_run
                                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'
                                        : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300'
                                "
                            >
                                <FlaskConical
                                    v-if="latest.dry_run"
                                    class="size-3.5"
                                />
                                {{
                                    latest.dry_run
                                        ? 'Simulación'
                                        : 'Carga realizada'
                                }}
                            </span>
                        </CardTitle>
                        <CardDescription>
                            <template v-if="latest">
                                {{ latest.file_name }} ·
                                {{ formatDateTime(latest.created_at) }}
                            </template>
                            <template v-else>
                                Aquí verás el resultado después de procesar un
                                archivo.
                            </template>
                        </CardDescription>
                    </CardHeader>
                    <CardContent v-if="latest" class="grid gap-5">
                        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div
                                v-for="item in countLabels"
                                :key="item.key"
                                class="rounded-lg border p-3"
                                :class="{
                                    'border-red-200 bg-red-50 dark:border-red-500/30 dark:bg-red-500/10':
                                        item.key === 'rejected' &&
                                        latest.counts.rejected > 0,
                                }"
                            >
                                <dt class="text-xs text-muted-foreground">
                                    {{ item.label }}
                                </dt>
                                <dd class="text-xl font-semibold tabular-nums">
                                    {{
                                        latest.counts[item.key].toLocaleString(
                                            'es',
                                        )
                                    }}
                                </dd>
                            </div>
                        </dl>

                        <p
                            v-if="latest.dry_run"
                            class="flex gap-2 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200"
                        >
                            <CircleAlert class="mt-0.5 size-4 shrink-0" />
                            Es una simulación: no se guardó nada. Si el
                            resultado es correcto, vuelve a subir el archivo sin
                            marcar «Solo simular».
                        </p>

                        <div
                            v-if="latest.rejection_sample.length"
                            class="grid gap-2"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-2"
                            >
                                <p class="text-sm font-semibold">
                                    Filas rechazadas
                                    <span
                                        v-if="
                                            latest.counts.rejected >
                                            latest.rejection_sample.length
                                        "
                                        class="font-normal text-muted-foreground"
                                    >
                                        (primeras
                                        {{ latest.rejection_sample.length }} de
                                        {{ latest.counts.rejected }})
                                    </span>
                                </p>
                                <Button
                                    v-if="latest.has_report"
                                    size="sm"
                                    variant="outline"
                                    as-child
                                >
                                    <a :href="rejectionsUrl(latest.id)">
                                        <Download /> Descargar todas
                                    </a>
                                </Button>
                            </div>
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="w-full text-sm">
                                    <thead
                                        class="bg-muted/50 text-left text-muted-foreground"
                                    >
                                        <tr>
                                            <th class="px-3 py-2 font-medium">
                                                Línea
                                            </th>
                                            <th class="px-3 py-2 font-medium">
                                                Código
                                            </th>
                                            <th class="px-3 py-2 font-medium">
                                                Motivo
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr
                                            v-for="rejection in latest.rejection_sample"
                                            :key="rejection.line"
                                        >
                                            <td class="px-3 py-2 tabular-nums">
                                                {{ rejection.line }}
                                            </td>
                                            <td class="px-3 py-2 font-mono">
                                                {{ rejection.code || '—' }}
                                            </td>
                                            <td class="px-3 py-2">
                                                {{ rejection.reason }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Historial de cargas</CardTitle>
                    <CardDescription>
                        Incluye las ejecutadas desde la consola con
                        <code>php artisan diagnoses:import</code>.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="imports.length === 0"
                        class="py-4 text-center text-sm text-muted-foreground"
                    >
                        Todavía no se ha procesado ningún archivo.
                    </p>
                    <div v-else class="overflow-x-auto rounded-lg border">
                        <table class="cards w-full text-sm">
                            <thead
                                class="bg-muted/50 text-left text-muted-foreground"
                            >
                                <tr>
                                    <th class="px-4 py-3 font-medium">Fecha</th>
                                    <th class="px-4 py-3 font-medium">
                                        Archivo
                                    </th>
                                    <th class="px-4 py-3 font-medium">Modo</th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Nuevos
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Actualizados
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Rechazados
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Deshabilitados
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="run in imports" :key="run.id">
                                    <td data-label="Fecha" class="px-4 py-3">
                                        {{ formatDateTime(run.created_at) }}
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ run.user ?? 'Consola' }}
                                        </p>
                                    </td>
                                    <td
                                        data-label="Archivo"
                                        class="px-4 py-3 break-all"
                                    >
                                        {{ run.file_name }}
                                    </td>
                                    <td data-label="Modo" class="px-4 py-3">
                                        {{
                                            run.dry_run ? 'Simulación' : 'Carga'
                                        }}
                                        <p
                                            v-if="run.deactivate_missing"
                                            class="text-xs text-muted-foreground"
                                        >
                                            Con deshabilitación
                                        </p>
                                    </td>
                                    <td
                                        data-label="Nuevos"
                                        class="px-4 py-3 text-right tabular-nums"
                                    >
                                        {{ run.counts.inserted }}
                                    </td>
                                    <td
                                        data-label="Actualizados"
                                        class="px-4 py-3 text-right tabular-nums"
                                    >
                                        {{ run.counts.updated }}
                                    </td>
                                    <td
                                        data-label="Rechazados"
                                        class="px-4 py-3 text-right tabular-nums"
                                    >
                                        {{ run.counts.rejected }}
                                    </td>
                                    <td
                                        data-label="Deshabilitados"
                                        class="px-4 py-3 text-right tabular-nums"
                                    >
                                        {{ run.counts.deactivated }}
                                    </td>
                                    <td data-label="" class="px-4 py-3">
                                        <div class="flex justify-end">
                                            <Button
                                                v-if="run.has_report"
                                                size="sm"
                                                variant="ghost"
                                                as-child
                                            >
                                                <a
                                                    :href="
                                                        rejectionsUrl(run.id)
                                                    "
                                                >
                                                    <Download /> Rechazos
                                                </a>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4">
                <h2 class="text-lg font-semibold">Códigos cargados</h2>

                <TableToolbar
                    v-model:search="filters.search"
                    placeholder="Buscar por código o descripción"
                    :can-reset="hasActiveFilters"
                    @reset="reset"
                >
                    <template #filters>
                        <div class="grid gap-1">
                            <Label
                                for="cie10-status"
                                class="text-xs text-muted-foreground"
                                >Estado</Label
                            >
                            <NativeSelect
                                id="cie10-status"
                                v-model="filters.status"
                                class="w-40"
                            >
                                <option value="">Todos</option>
                                <option value="active">Habilitados</option>
                                <option value="inactive">Deshabilitados</option>
                            </NativeSelect>
                        </div>
                    </template>
                </TableToolbar>

                <div class="overflow-x-auto rounded-lg border">
                    <table class="cards w-full text-sm">
                        <thead
                            class="bg-muted/50 text-left text-muted-foreground"
                        >
                            <tr>
                                <th class="px-4 py-3 font-medium">Código</th>
                                <th class="px-4 py-3 font-medium">
                                    Descripción
                                </th>
                                <th class="px-4 py-3 font-medium">Capítulo</th>
                                <th class="px-4 py-3 font-medium">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-if="codes.data.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-10 text-center text-muted-foreground"
                                >
                                    No se encontraron códigos.
                                </td>
                            </tr>
                            <tr v-for="code in codes.data" :key="code.id">
                                <td
                                    data-label="Código"
                                    class="px-4 py-3 font-mono font-semibold"
                                >
                                    {{ code.code }}
                                </td>
                                <td data-label="Descripción" class="px-4 py-3">
                                    {{ code.description }}
                                    <p
                                        v-if="code.category"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ code.category }}
                                    </p>
                                </td>
                                <td
                                    data-label="Capítulo"
                                    class="px-4 py-3 text-xs text-muted-foreground"
                                >
                                    {{ code.chapter ?? '—' }}
                                </td>
                                <td data-label="Estado" class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap"
                                        :class="
                                            code.is_active
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300'
                                                : 'bg-neutral-200 text-neutral-700 dark:bg-neutral-500/20 dark:text-neutral-300'
                                        "
                                    >
                                        {{
                                            code.is_active
                                                ? 'Habilitado'
                                                : 'Deshabilitado'
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :paginator="codes" />
            </div>
        </div>
    </AppLayout>
</template>
