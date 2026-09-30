<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    Ban,
    CircleCheck,
    Eye,
    FileText,
    Plus,
    ReceiptText,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import DateRangeFilter from '@/components/DateRangeFilter.vue';
import IconButton from '@/components/IconButton.vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatDateTime, formatMoney } from '@/lib/format';
import patientRoutes from '@/routes/patients';
import paymentRoutes from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';
import type { Option, Paginated, Payment, TableFilters } from '@/types/models';

const props = defineProps<{
    payments: Paginated<Payment>;
    totals: { paid: number; pending: number };
    filters: TableFilters;
    statuses: Option[];
    settling: Payment | null;
    methods: Option[];
    can: { create: boolean };
}>();

const { filters, activeFilters, hasActiveFilters, reset } = useTableFilters(
    () => paymentRoutes.index().url,
    {
        search: props.filters.search,
        status: props.filters.status,
        from: props.filters.from,
        to: props.filters.to,
    },
);

const exportUrl = (format: 'xlsx' | 'pdf') =>
    paymentRoutes.export({ query: { ...activeFilters.value, format } }).url;

const page = usePage();
const isPatient = computed(() => page.props.auth.role?.value === 'patient');
const title = computed(() => (isPatient.value ? 'Mis pagos' : 'Pagos'));

const today = new Date().toISOString().slice(0, 10);
const payingPayment = ref<Payment | null>(props.settling);
const payDialogOpen = computed({
    get: () => payingPayment.value !== null,
    set: (open) => {
        if (!open) {
            payingPayment.value = null;
        }
    },
});

const voidingPayment = ref<Payment | null>(null);
const voidDialogOpen = computed({
    get: () => voidingPayment.value !== null,
    set: (open) => {
        if (!open) {
            voidingPayment.value = null;
        }
    },
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pagos', href: paymentRoutes.index() },
];
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <Button v-if="can.create" as-child>
                    <Link :href="paymentRoutes.create()"
                        ><Plus /> Registrar pago</Link
                    >
                </Button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:max-w-2xl">
                <Card class="gap-2">
                    <CardHeader>
                        <CardDescription>Total pagado</CardDescription>
                        <CardTitle class="text-2xl tabular-nums">{{
                            formatMoney(totals.paid)
                        }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card class="gap-2">
                    <CardHeader>
                        <CardDescription>Pendiente de pago</CardDescription>
                        <CardTitle
                            class="text-2xl text-amber-600 tabular-nums dark:text-amber-400"
                        >
                            {{ formatMoney(totals.pending) }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <TableToolbar
                v-model:search="filters.search"
                :placeholder="
                    isPatient
                        ? 'Buscar por concepto o referencia'
                        : 'Buscar por paciente, concepto o referencia'
                "
                :export-url="exportUrl"
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
                    <DateRangeFilter
                        v-model:from="filters.from"
                        v-model:to="filters.to"
                    />
                </template>
            </TableToolbar>

            <div class="overflow-x-auto rounded-lg border">
                <table class="cards w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Fecha</th>
                            <th v-if="!isPatient" class="px-4 py-3 font-medium">
                                Paciente
                            </th>
                            <th class="px-4 py-3 font-medium">Concepto</th>
                            <th class="px-4 py-3 font-medium">Método</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Monto
                            </th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                            <th class="px-4 py-3 font-medium">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="props.payments.data.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No hay pagos registrados.
                            </td>
                        </tr>
                        <tr
                            v-for="payment in props.payments.data"
                            :key="payment.id"
                        >
                            <td
                                data-label="Fecha"
                                class="px-4 py-3 whitespace-nowrap"
                            >
                                {{ formatDate(payment.paid_at) }}
                            </td>
                            <td
                                data-label="Paciente"
                                v-if="!isPatient"
                                class="px-4 py-3"
                            >
                                <Link
                                    :href="
                                        patientRoutes.show(payment.patient!.id)
                                    "
                                    class="font-medium hover:underline"
                                >
                                    {{ payment.patient?.full_name }}
                                </Link>
                            </td>
                            <td data-label="Concepto" class="px-4 py-3">
                                {{ payment.concept }}
                                <p
                                    v-if="payment.appointment"
                                    class="text-xs text-muted-foreground"
                                >
                                    Cita
                                    {{
                                        formatDateTime(
                                            payment.appointment.scheduled_at,
                                        )
                                    }}
                                    · {{ payment.appointment.doctor }}
                                </p>
                                <p
                                    v-if="payment.reference"
                                    class="text-xs text-muted-foreground"
                                >
                                    Ref. {{ payment.reference }}
                                </p>
                            </td>
                            <td data-label="Método" class="px-4 py-3">
                                {{ payment.method.label }}
                            </td>
                            <td
                                data-label="Monto"
                                class="px-4 py-3 text-right font-medium tabular-nums"
                            >
                                {{ formatMoney(payment.amount) }}
                            </td>
                            <td data-label="Estado" class="px-4 py-3">
                                <StatusBadge :status="payment.status" />
                            </td>
                            <td data-label="" class="px-4 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <IconButton
                                        label="Ver detalle"
                                        tone="info"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                paymentRoutes.show(payment.id)
                                            "
                                        >
                                            <Eye />
                                        </Link>
                                    </IconButton>
                                    <IconButton
                                        v-if="payment.can.download_invoice"
                                        label="Ver factura (PDF)"
                                        tone="danger"
                                        as-child
                                    >
                                        <a
                                            :href="
                                                paymentRoutes.invoice(
                                                    payment.id,
                                                ).url
                                            "
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            <FileText />
                                        </a>
                                    </IconButton>
                                    <IconButton
                                        v-if="
                                            can.create && payment.can.mark_paid
                                        "
                                        label="Marcar como pagado"
                                        tone="success"
                                        @click="payingPayment = payment"
                                    >
                                        <CircleCheck />
                                    </IconButton>
                                    <IconButton
                                        v-if="can.create && payment.can.void"
                                        label="Anular pago"
                                        tone="danger"
                                        @click="voidingPayment = payment"
                                    >
                                        <Ban />
                                    </IconButton>
                                    <IconButton
                                        v-if="can.create && payment.patient"
                                        label="Registrar otro pago igual"
                                        tone="warning"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                paymentRoutes.create({
                                                    query: {
                                                        payment_id: payment.id,
                                                    },
                                                })
                                            "
                                        >
                                            <ReceiptText />
                                        </Link>
                                    </IconButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :paginator="props.payments" />

            <Dialog v-model:open="payDialogOpen">
                <DialogContent v-if="payingPayment" class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Marcar como pagado</DialogTitle>
                        <DialogDescription>
                            {{ payingPayment.patient?.full_name }} ·
                            {{ payingPayment.concept }} ·
                            {{ formatMoney(payingPayment.amount) }}
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        v-if="payingPayment.appointment"
                        class="rounded-md border bg-muted/40 p-3 text-sm"
                    >
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase"
                        >
                            Cita a pagar
                        </p>
                        <p class="mt-1 font-medium">
                            {{
                                formatDateTime(
                                    payingPayment.appointment.scheduled_at,
                                )
                            }}
                        </p>
                        <p>
                            {{ payingPayment.appointment.doctor }} ·
                            {{ payingPayment.appointment.reason }}
                        </p>
                        <StatusBadge
                            class="mt-2"
                            :status="payingPayment.appointment.status"
                        />
                    </div>
                    <p
                        v-else
                        class="rounded-md border border-dashed p-3 text-sm text-muted-foreground"
                    >
                        Este pago no está asociado a una cita.
                    </p>

                    <Form
                        v-bind="
                            PaymentController.markPaid.form(payingPayment.id)
                        "
                        class="grid gap-4"
                        :options="{ preserveScroll: true }"
                        v-slot="{ errors, processing }"
                        @success="payingPayment = null"
                    >
                        <div class="grid gap-2">
                            <Label for="quick-method">Método de pago *</Label>
                            <NativeSelect
                                id="quick-method"
                                name="method"
                                :default-value="payingPayment.method.value"
                                required
                            >
                                <option
                                    v-for="method in methods"
                                    :key="method.value"
                                    :value="method.value"
                                >
                                    {{ method.label }}
                                </option>
                            </NativeSelect>
                            <InputError :message="errors.method" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="quick-paid-at">Fecha de pago *</Label>
                            <Input
                                id="quick-paid-at"
                                type="date"
                                name="paid_at"
                                :default-value="today"
                                :max="today"
                                required
                            />
                            <InputError :message="errors.paid_at" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="quick-reference">
                                Referencia / N.º de comprobante
                            </Label>
                            <Input
                                id="quick-reference"
                                name="reference"
                                :default-value="payingPayment.reference ?? ''"
                            />
                            <InputError :message="errors.reference" />
                        </div>
                        <Button :disabled="processing">Confirmar pago</Button>
                    </Form>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="voidDialogOpen">
                <DialogContent v-if="voidingPayment" class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Anular pago</DialogTitle>
                        <DialogDescription>
                            {{ voidingPayment.patient?.full_name }} ·
                            {{ voidingPayment.concept }} ·
                            {{ formatMoney(voidingPayment.amount) }}
                        </DialogDescription>
                    </DialogHeader>

                    <p
                        v-if="voidingPayment.can.download_invoice"
                        class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        Este pago ya está cobrado: al anularlo dejará de contar
                        en el total pagado y su factura ya no estará
                        disponible.
                    </p>

                    <Form
                        v-bind="PaymentController.void.form(voidingPayment.id)"
                        class="grid gap-4"
                        :options="{ preserveScroll: true }"
                        v-slot="{ errors, processing }"
                        @success="voidingPayment = null"
                    >
                        <div class="grid gap-2">
                            <Label for="void-reason">
                                Motivo de la anulación *
                            </Label>
                            <Input
                                id="void-reason"
                                name="reason"
                                maxlength="255"
                                required
                            />
                            <InputError :message="errors.reason" />
                        </div>
                        <Button variant="destructive" :disabled="processing">
                            Anular pago
                        </Button>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
