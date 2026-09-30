<script setup lang="ts">
import {
    CalendarDays,
    ClipboardList,
    LayoutGrid,
    Sparkles,
    UserRound,
    Wallet,
} from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Step = {
    icon: Component;
    tone: string;
    title: string;
    description: string;
};

const open = defineModel<boolean>('open', { required: true });

const steps: Step[] = [
    {
        icon: Sparkles,
        tone: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
        title: 'Te damos la bienvenida',
        description:
            'Aquí puedes ver tu historial médico, gestionar tus citas y revisar tus pagos en un solo lugar. Te mostramos rápidamente cómo funciona.',
    },
    {
        icon: LayoutGrid,
        tone: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        title: 'Inicio',
        description:
            'Tu panel resume tus próximas citas, tus consultas recientes y tus pagos pendientes. También te recuerda cuando debes completar o actualizar tus datos.',
    },
    {
        icon: CalendarDays,
        tone: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        title: 'Mis citas',
        description:
            'Solicita una cita eligiendo médico, fecha y hora. La clínica la confirma y te avisa por correo. Puedes verlas en calendario o en lista, y cancelarlas si no podrás asistir.',
    },
    {
        icon: ClipboardList,
        tone: 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300',
        title: 'Mi historial',
        description:
            'Consulta tus consultas, diagnósticos, recetas y archivos adjuntos. Esta información es privada: solo tú y tu equipo médico pueden verla.',
    },
    {
        icon: Wallet,
        tone: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        title: 'Mis pagos',
        description:
            'Revisa lo que ya pagaste y lo que tienes pendiente, y abre el detalle de cada pago con el botón del ojo.',
    },
    {
        icon: UserRound,
        tone: 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
        title: 'Tus datos',
        description:
            'Completa tus datos básicos antes de tu primera consulta; luego quedan registrados. Tu teléfono, dirección y contacto de emergencia puedes actualizarlos siempre. Desde tu perfil también puedes elegir el tema claro u oscuro.',
    },
];

const index = ref(0);

const step = computed(() => steps[index.value]);
const isFirst = computed(() => index.value === 0);
const isLast = computed(() => index.value === steps.length - 1);

watch(open, (value) => {
    if (value) {
        index.value = 0;
    }
});

function next() {
    if (isLast.value) {
        open.value = false;

        return;
    }

    index.value++;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="items-center text-center sm:text-center">
                <div
                    class="mb-2 flex size-14 items-center justify-center rounded-full"
                    :class="step.tone"
                >
                    <component :is="step.icon" class="size-7" />
                </div>
                <DialogTitle>{{ step.title }}</DialogTitle>
                <DialogDescription class="text-balance">
                    {{ step.description }}
                </DialogDescription>
            </DialogHeader>

            <div
                class="flex justify-center gap-1.5"
                role="presentation"
                aria-hidden="true"
            >
                <span
                    v-for="(_, dot) in steps"
                    :key="dot"
                    class="h-1.5 rounded-full transition-all"
                    :class="
                        dot === index
                            ? 'w-6 bg-primary'
                            : 'w-1.5 bg-muted-foreground/30'
                    "
                />
            </div>

            <div class="flex items-center justify-between gap-2">
                <Button variant="ghost" size="sm" @click="open = false">
                    Omitir
                </Button>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="!isFirst"
                        variant="outline"
                        size="sm"
                        @click="index--"
                    >
                        Anterior
                    </Button>
                    <Button size="sm" @click="next">
                        {{ isLast ? 'Empezar' : 'Siguiente' }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
