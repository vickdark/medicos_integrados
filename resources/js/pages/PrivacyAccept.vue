<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { formatDate } from '@/lib/format';
import { logout, privacy } from '@/routes';
import privacyAccept from '@/routes/privacy/accept';

defineProps<{
    version: string;
    updatedAt: string;
    previousVersion: string | null;
}>();
</script>

<template>
    <AuthBase
        title="Política de tratamiento de datos"
        :description="
            previousVersion
                ? 'Actualizamos la política. Para continuar, revísala y confirma tu autorización.'
                : 'Para usar tu cuenta, revisa la política y confirma tu autorización.'
        "
    >
        <Head title="Aceptar la política de datos" />

        <Form
            v-bind="privacyAccept.store.form()"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <p class="text-sm text-muted-foreground">
                Versión {{ version }} · {{ formatDate(updatedAt) }}
                <template v-if="previousVersion">
                    (aceptaste antes la versión {{ previousVersion }})
                </template>
            </p>

            <div class="grid gap-2">
                <div class="flex items-start gap-3">
                    <Checkbox id="privacy" name="privacy" class="mt-0.5" />
                    <Label
                        for="privacy"
                        class="block text-sm leading-snug font-normal"
                    >
                        Acepto la
                        <a
                            :href="privacy().url"
                            target="_blank"
                            rel="noopener"
                            class="underline underline-offset-4"
                            >política de tratamiento de datos personales</a
                        >
                        y autorizo el tratamiento de mis datos personales,
                        incluidos los datos sensibles de salud, para mi atención
                        médica.
                    </Label>
                </div>
                <InputError :message="errors.privacy" />
            </div>

            <Button
                type="submit"
                class="w-full"
                :disabled="processing"
                data-test="accept-privacy-button"
            >
                <Spinner v-if="processing" />
                Aceptar y continuar
            </Button>

            <Button variant="ghost" class="w-full" as-child>
                <Link :href="logout()" method="post" as="button"
                    >Salir sin aceptar</Link
                >
            </Button>
        </Form>
    </AuthBase>
</template>
