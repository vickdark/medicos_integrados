<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Check, RotateCcw } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import BrandingController from '@/actions/App/Http/Controllers/Settings/BrandingController';
import LandingSettingsController from '@/actions/App/Http/Controllers/Settings/LandingSettingsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { confirmAction } from '@/lib/confirm';
import { edit } from '@/routes/branding';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    color: string;
    defaultColor: string;
    showDoctors: boolean;
    presets: { name: string; color: string }[];
}>();

const MIN_CONTRAST = 3;

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Personalización', href: edit() },
];

const chosen = ref(props.color);

const isValid = computed(() => /^#[0-9a-fA-F]{6}$/.test(chosen.value));

function luminance(hex: string): number {
    const [red, green, blue] = [1, 3, 5].map((start) => {
        const channel = parseInt(hex.slice(start, start + 2), 16) / 255;

        return channel <= 0.03928
            ? channel / 12.92
            : ((channel + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
}

const contrast = computed(() =>
    isValid.value ? 1.05 / (luminance(chosen.value) + 0.05) : 0,
);
const readable = computed(() => contrast.value >= MIN_CONTRAST);
const isSaved = computed(
    () => chosen.value.toLowerCase() === props.color.toLowerCase(),
);
const isDefault = computed(
    () => props.color.toLowerCase() === props.defaultColor.toLowerCase(),
);

function applyPreview(value: string) {
    document.documentElement.style.setProperty('--brand', value);
}

watch(chosen, (value) => {
    if (/^#[0-9a-fA-F]{6}$/.test(value)) {
        applyPreview(value);
    }
});

watch(
    () => props.color,
    (value) => {
        chosen.value = value;
        applyPreview(value);
    },
);

onBeforeUnmount(() => applyPreview(props.color));

function pick(color: string) {
    chosen.value = color;
}

async function reset() {
    const accepted = await confirmAction({
        title: 'Restablecer color',
        text: 'La aplicación volverá al color original para todos los usuarios.',
        confirmText: 'Sí, restablecer',
        cancelText: 'Volver',
        tone: 'primary',
    });

    if (accepted) {
        router.delete(BrandingController.destroy().url, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Personalización" />

        <h1 class="sr-only">Personalización</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Color de la aplicación"
                    description="Elige el color de acento que ven todos los usuarios: logo, gráficos, etiquetas, la página de inicio y los documentos PDF y Excel."
                />

                <Form
                    v-bind="BrandingController.update.form()"
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-3">
                        <Label>Colores sugeridos</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="preset in presets"
                                :key="preset.color"
                                type="button"
                                class="relative flex size-9 items-center justify-center rounded-full border-2 border-background ring-1 ring-border transition-transform hover:scale-110 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                :style="{ backgroundColor: preset.color }"
                                :title="preset.name"
                                :aria-label="preset.name"
                                :aria-pressed="
                                    chosen.toLowerCase() ===
                                    preset.color.toLowerCase()
                                "
                                @click="pick(preset.color)"
                            >
                                <Check
                                    v-if="
                                        chosen.toLowerCase() ===
                                        preset.color.toLowerCase()
                                    "
                                    class="size-4 text-white"
                                />
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="color-hex">Color personalizado</Label>
                        <div class="flex items-center gap-3">
                            <input
                                type="color"
                                class="h-9 w-12 cursor-pointer rounded-md border bg-transparent p-0.5"
                                :value="isValid ? chosen : props.color"
                                aria-label="Selector de color"
                                @input="
                                    chosen = (
                                        $event.target as HTMLInputElement
                                    ).value.toUpperCase()
                                "
                            />
                            <Input
                                id="color-hex"
                                v-model="chosen"
                                class="w-32 font-mono uppercase"
                                maxlength="7"
                                placeholder="#0D9488"
                            />
                            <input type="hidden" name="color" :value="chosen" />
                        </div>
                        <InputError :message="errors.color" />
                        <p
                            v-if="isValid && !readable"
                            class="text-sm text-amber-700 dark:text-amber-400"
                        >
                            Este color es muy claro: el texto blanco sobre
                            botones y etiquetas no se leería bien. Elige uno más
                            oscuro.
                        </p>
                    </div>

                    <div class="grid gap-3 rounded-xl border p-4">
                        <p class="text-sm font-medium">Vista previa</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                class="inline-flex h-9 items-center rounded-md bg-brand-600 px-4 text-sm font-medium text-white"
                            >
                                Botón
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800 dark:bg-brand-500/15 dark:text-brand-300"
                            >
                                Etiqueta
                            </span>
                            <span
                                class="inline-flex items-center rounded-md border border-brand-300 bg-brand-50 px-3 py-1.5 text-sm text-brand-900 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-200"
                            >
                                Aviso
                            </span>
                        </div>
                        <div
                            class="flex h-16 items-end gap-1.5"
                            aria-hidden="true"
                        >
                            <div
                                v-for="(height, index) in [
                                    30, 45, 38, 60, 52, 80,
                                ]"
                                :key="index"
                                class="w-6 rounded-t-[4px]"
                                :class="
                                    index === 5
                                        ? 'bg-brand-600 dark:bg-brand-400'
                                        : 'bg-brand-300 dark:bg-brand-800'
                                "
                                :style="{ height: `${height}%` }"
                            />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            :disabled="
                                processing || !isValid || !readable || isSaved
                            "
                        >
                            Guardar color
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            :disabled="isDefault"
                            @click="reset"
                        >
                            <RotateCcw /> Restablecer original
                        </Button>
                    </div>
                </Form>

                <div class="space-y-4 border-t pt-8">
                    <Heading
                        variant="small"
                        title="Página de inicio"
                        description="Decide qué información pública se muestra en la sección Quiénes somos."
                    />

                    <Form
                        v-bind="LandingSettingsController.update.form()"
                        :options="{ preserveScroll: true }"
                        class="space-y-4"
                        v-slot="{ processing }"
                    >
                        <input type="hidden" name="show_doctors" value="0" />
                        <label
                            class="flex items-start gap-3 rounded-lg border p-4"
                        >
                            <input
                                type="checkbox"
                                name="show_doctors"
                                value="1"
                                class="mt-1 size-4"
                                :checked="showDoctors"
                            />
                            <span class="grid gap-1 text-sm">
                                <span class="font-medium">
                                    Mostrar a los médicos en la página de inicio
                                </span>
                                <span class="text-muted-foreground">
                                    Se publican en tarjetas el nombre, la
                                    especialidad, la reseña y la foto de los
                                    médicos con la cuenta activa. Está
                                    desactivado hasta que lo habilites. Sus
                                    fotos se cargan desde Usuarios, al editar al
                                    médico.
                                </span>
                            </span>
                        </label>
                        <Button :disabled="processing">
                            Guardar página de inicio
                        </Button>
                    </Form>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
