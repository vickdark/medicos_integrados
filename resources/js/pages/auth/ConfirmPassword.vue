<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { dashboard } from '@/routes';
import { store } from '@/routes/password/confirm';

function goBack() {
    if (window.history.length > 1) {
        window.history.back();

        return;
    }

    router.visit(dashboard());
}
</script>

<template>
    <AuthLayout
        title="Confirma tu contraseña"
        description="Esta es un área protegida. Confirma tu contraseña para continuar."
    >
        <Head title="Confirmar contraseña" />

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label htmlFor="password">Contraseña</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="current-password"
                        autofocus
                    />

                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center">
                    <Button
                        class="w-full"
                        :disabled="processing"
                        data-test="confirm-password-button"
                    >
                        <Spinner v-if="processing" />
                        Confirmar contraseña
                    </Button>
                </div>

                <Button
                    type="button"
                    variant="ghost"
                    class="w-full"
                    data-test="confirm-password-back-button"
                    @click="goBack"
                >
                    Volver
                </Button>
            </div>
        </Form>
    </AuthLayout>
</template>
