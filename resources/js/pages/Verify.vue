<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    CircleCheck,
    CircleX,
    HeartPulse,
    Search,
    ShieldAlert,
} from 'lucide-vue-next';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import { home } from '@/routes';
import verificationRoutes from '@/routes/verification';

defineProps<{
    code: string | null;
    result: {
        type: string;
        number: string;
        issued_at: string;
        signed: boolean;
        doctor: { name: string; specialty: string; license_number: string };
        patient: { initials: string; document: string | null };
        facts: { label: string; value: string | string[] }[];
    } | null;
}>();
</script>

<template>
    <Head title="Verificación de documentos" />

    <div class="min-h-svh bg-background">
        <header class="border-b">
            <div
                class="mx-auto flex h-14 max-w-2xl items-center justify-between px-4"
            >
                <Link
                    :href="home()"
                    class="flex items-center gap-2 font-semibold"
                >
                    <HeartPulse class="size-5 text-brand-600" /> Médicos
                    Integrados
                </Link>
                <ThemeToggle />
            </div>
        </header>

        <main class="mx-auto grid max-w-2xl gap-6 px-4 py-10">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Verificación de documentos
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Comprueba que una receta, incapacidad, remisión, orden de
                    exámenes o consentimiento fue emitido por la clínica.
                    Escanea el código QR del documento o escribe su código.
                </p>
            </div>

            <Form
                v-bind="verificationRoutes.index.form()"
                class="flex flex-wrap items-end gap-2"
            >
                <div class="grid flex-1 gap-1.5">
                    <Label for="codigo">Código de verificación</Label>
                    <Input
                        id="codigo"
                        name="codigo"
                        :default-value="code ?? ''"
                        placeholder="Ej.: D7KQ-4M2X-P9RT"
                        autocomplete="off"
                        class="font-mono uppercase"
                        required
                    />
                </div>
                <Button><Search /> Verificar</Button>
            </Form>

            <section
                v-if="code && result"
                class="grid gap-5 rounded-xl border border-emerald-300 bg-emerald-50/50 p-5 dark:border-emerald-500/40 dark:bg-emerald-500/5"
            >
                <div class="flex items-start gap-3">
                    <CircleCheck
                        class="mt-0.5 size-6 shrink-0 text-emerald-600 dark:text-emerald-400"
                    />
                    <div>
                        <p class="text-lg font-semibold">
                            Documento emitido por Médicos Integrados
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ result.type }} · N.º {{ result.number }} · código
                            {{ code }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="!result.signed"
                    class="flex items-start gap-2 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-900 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200"
                >
                    <ShieldAlert class="mt-0.5 size-4 shrink-0" />
                    <span>
                        <strong
                            >No válido por falta de firma del médico.</strong
                        >
                        El documento existe, pero el médico aún no ha registrado
                        su firma en el sistema.
                    </span>
                </p>

                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Fecha de emisión</dt>
                        <dd class="font-medium">
                            {{ formatDateTime(result.issued_at) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Paciente</dt>
                        <dd class="font-medium">
                            {{ result.patient.initials }}
                            <span
                                v-if="result.patient.document"
                                class="text-muted-foreground"
                            >
                                · {{ result.patient.document }}</span
                            >
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-muted-foreground">Médico</dt>
                        <dd class="font-medium">
                            {{ result.doctor.name }}
                            <span class="font-normal text-muted-foreground">
                                · {{ result.doctor.specialty }} · Registro
                                médico {{ result.doctor.license_number }}
                            </span>
                        </dd>
                    </div>
                    <div
                        v-for="fact in result.facts"
                        :key="fact.label"
                        :class="{ 'sm:col-span-2': Array.isArray(fact.value) }"
                    >
                        <dt class="text-muted-foreground">{{ fact.label }}</dt>
                        <dd class="font-medium">
                            <ul
                                v-if="Array.isArray(fact.value)"
                                class="list-disc pl-5"
                            >
                                <li v-for="item in fact.value" :key="item">
                                    {{ item }}
                                </li>
                            </ul>
                            <template v-else>{{ fact.value }}</template>
                        </dd>
                    </div>
                </dl>

                <p class="text-xs text-muted-foreground">
                    Compara estos datos con el documento impreso: si alguno no
                    coincide, el documento fue alterado. Por privacidad no se
                    muestran el diagnóstico ni los datos completos del paciente.
                </p>
            </section>

            <section
                v-else-if="code"
                class="flex items-start gap-3 rounded-xl border border-red-300 bg-red-50 p-5 text-red-900 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200"
            >
                <CircleX class="mt-0.5 size-6 shrink-0" />
                <div>
                    <p class="text-lg font-semibold">
                        No encontramos un documento con este código
                    </p>
                    <p class="text-sm">
                        Revisa que el código {{ code }} esté bien escrito. Si es
                        correcto, el documento no fue emitido por la clínica y
                        no debe aceptarse.
                    </p>
                </div>
            </section>
        </main>
    </div>
</template>
