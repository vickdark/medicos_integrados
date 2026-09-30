<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Check, ClipboardList, FileText, HandCoins, X } from 'lucide-vue-next';
import { computed } from 'vue';
import IconButton from '@/components/IconButton.vue';
import { confirmAction } from '@/lib/confirm';
import appointmentRoutes from '@/routes/appointments';
import consultationRoutes from '@/routes/consultations';
import paymentRoutes from '@/routes/payments';
import type { Appointment, AppointmentStatusValue } from '@/types/models';

const props = defineProps<{
    appointment: Appointment;
    role: string;
}>();

const emit = defineEmits<{ changed: [] }>();

const canManagePayments = computed(
    () =>
        props.appointment.can.settle_payment ||
        props.appointment.can.register_payment,
);

const paymentsHref = computed(() =>
    props.appointment.pending_payment_id
        ? paymentRoutes.index({
              query: { pay: props.appointment.pending_payment_id },
          })
        : paymentRoutes.create({
              query: { appointment_id: props.appointment.id },
          }),
);

async function changeStatus(status: AppointmentStatusValue) {
    const cancelling = status === 'cancelled';

    const accepted = await confirmAction({
        title: cancelling ? 'Cancelar cita' : 'Confirmar cita',
        text: cancelling
            ? 'Se notificará a la otra parte y la cita quedará cancelada.'
            : 'Se notificará al paciente que su cita está confirmada.',
        confirmText: cancelling ? 'Sí, cancelar' : 'Sí, confirmar',
        cancelText: 'Volver',
        tone: cancelling ? 'danger' : 'success',
    });

    if (!accepted) {
        return;
    }

    router.patch(
        appointmentRoutes.status(props.appointment.id).url,
        { status },
        { preserveScroll: true, onSuccess: () => emit('changed') },
    );
}
</script>

<template>
    <div class="flex flex-wrap justify-end gap-1.5">
        <IconButton
            v-if="
                appointment.consultation_id && appointment.can.view_consultation
            "
            label="Ver consulta"
            tone="violet"
            as-child
        >
            <Link :href="consultationRoutes.show(appointment.consultation_id)">
                <FileText />
            </Link>
        </IconButton>
        <template v-if="appointment.can.update_status">
            <IconButton
                v-if="role === 'doctor'"
                label="Atender"
                variant="default"
                as-child
            >
                <Link
                    :href="
                        consultationRoutes.create(appointment.patient!.id, {
                            query: { appointment_id: appointment.id },
                        })
                    "
                >
                    <ClipboardList />
                </Link>
            </IconButton>
            <IconButton
                v-if="
                    role !== 'patient' &&
                    appointment.status.value === 'requested'
                "
                label="Confirmar cita"
                tone="success"
                @click="changeStatus('confirmed')"
            >
                <Check />
            </IconButton>
        </template>
        <Link
            v-if="canManagePayments"
            :href="paymentsHref"
            class="inline-flex h-8 items-center gap-1.5 rounded-full border border-amber-300 bg-amber-50 px-3 text-xs font-medium whitespace-nowrap text-amber-800 transition-colors hover:bg-amber-100 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20"
        >
            <HandCoins class="size-3.5" /> Gestionar pagos
        </Link>
        <IconButton
            v-if="appointment.can.update_status"
            label="Cancelar cita"
            tone="danger"
            @click="changeStatus('cancelled')"
        >
            <X />
        </IconButton>
    </div>
</template>
