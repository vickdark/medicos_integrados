<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Clock, PenLine, ShieldAlert, Trash2 } from 'lucide-vue-next';
import DoctorSignatureController from '@/actions/App/Http/Controllers/Settings/DoctorSignatureController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { confirmAction } from '@/lib/confirm';
import { formatMoney } from '@/lib/format';
import { show } from '@/routes/doctor-profile';
import scheduleRoutes from '@/routes/schedules';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    profile: {
        id: number;
        name: string;
        email: string;
        specialty: string;
        license_number: string;
        phone: string | null;
        consultation_fee: string;
        slot_minutes: number;
        bio: string | null;
        photo_url: string | null;
        signature_url: string | null;
        schedule_summary: { days: string; ranges: string[] }[];
    };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Perfil profesional', href: show() },
];

async function removeSignature() {
    const accepted = await confirmAction({
        title: 'Quitar tu firma',
        text: 'Tus recetas y documentos se marcarán como no válidos hasta que registres una nueva.',
        confirmText: 'Sí, quitar',
        cancelText: 'Volver',
        tone: 'danger',
    });

    if (accepted) {
        router.delete(DoctorSignatureController.destroy().url, {
            preserveScroll: true,
        });
    }
}

const details = [
    { label: 'Nombre', value: props.profile.name },
    { label: 'Correo', value: props.profile.email },
    { label: 'Especialidad', value: props.profile.specialty },
    { label: 'Registro médico', value: props.profile.license_number },
    { label: 'Teléfono', value: props.profile.phone },
    {
        label: 'Tarifa de consulta',
        value: formatMoney(props.profile.consultation_fee),
    },
    {
        label: 'Duración de cada cita',
        value: `${props.profile.slot_minutes} minutos`,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Perfil profesional" />

        <h1 class="sr-only">Perfil profesional</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Perfil profesional"
                    description="Tus datos como médico en la clínica. Si algo debe corregirse, pídelo al administrador."
                />

                <img
                    v-if="profile.photo_url"
                    :src="profile.photo_url"
                    :alt="`Foto de ${profile.name}`"
                    class="size-28 rounded-2xl border object-cover"
                />

                <dl class="grid gap-3 text-sm">
                    <div
                        v-for="detail in details"
                        :key="detail.label"
                        class="grid grid-cols-3 gap-2"
                    >
                        <dt class="text-muted-foreground">
                            {{ detail.label }}
                        </dt>
                        <dd class="col-span-2 break-words">
                            {{ detail.value || '—' }}
                        </dd>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <dt class="text-muted-foreground">Reseña</dt>
                        <dd class="col-span-2 break-words whitespace-pre-line">
                            {{ profile.bio || '—' }}
                        </dd>
                    </div>
                </dl>

                <div class="grid gap-3">
                    <h3 class="text-sm font-semibold">Firma</h3>
                    <p
                        v-if="!profile.signature_url"
                        class="flex items-start gap-2 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-900 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200"
                    >
                        <ShieldAlert class="mt-0.5 size-4 shrink-0" />
                        <span>
                            <strong>Aún no registras tu firma.</strong> Tus
                            recetas y documentos clínicos salen marcados como no
                            válidos y no se pueden enviar por correo.
                        </span>
                    </p>
                    <div
                        v-else
                        class="flex h-24 w-60 items-center justify-center rounded-xl border bg-white p-2"
                    >
                        <img
                            :src="profile.signature_url"
                            alt="Tu firma"
                            class="max-h-full max-w-full object-contain"
                        />
                    </div>
                    <Form
                        v-bind="DoctorSignatureController.update.form()"
                        class="grid gap-2"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        v-slot="{ errors, processing }"
                    >
                        <Label for="signature">
                            {{
                                profile.signature_url
                                    ? 'Reemplazar firma'
                                    : 'Subir firma'
                            }}
                        </Label>
                        <div class="flex flex-wrap items-center gap-2">
                            <Input
                                id="signature"
                                type="file"
                                name="signature"
                                accept="image/png,image/jpeg,image/webp"
                                class="max-w-xs"
                                required
                            />
                            <Button size="sm" :disabled="processing">
                                <PenLine /> Guardar firma
                            </Button>
                            <Button
                                v-if="profile.signature_url"
                                type="button"
                                size="sm"
                                variant="ghost"
                                class="text-destructive"
                                @click="removeSignature"
                            >
                                <Trash2 /> Quitar
                            </Button>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Firma escaneada o fotografiada sobre fondo blanco
                            (PNG, JPG o WebP de hasta 1 MB).
                        </p>
                        <InputError :message="errors.signature" />
                    </Form>
                </div>

                <div class="grid gap-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold">
                            Horario de atención
                        </h3>
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="scheduleRoutes.index(profile.id)">
                                <Clock /> Administrar horario
                            </Link>
                        </Button>
                    </div>
                    <ul
                        v-if="profile.schedule_summary.length"
                        class="divide-y rounded-lg border"
                    >
                        <li
                            v-for="row in profile.schedule_summary"
                            :key="row.days"
                            class="flex flex-wrap items-center gap-x-4 gap-y-1.5 px-4 py-2.5 text-sm"
                        >
                            <span class="w-24 shrink-0 font-medium">{{
                                row.days
                            }}</span>
                            <span class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="range in row.ranges"
                                    :key="range"
                                    class="rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium whitespace-nowrap text-emerald-800 tabular-nums dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300"
                                    >{{ range }}</span
                                >
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        Aún no tienes un horario registrado.
                    </p>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
