<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import LandingContentController from '@/actions/App/Http/Controllers/Settings/LandingContentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/landing-content';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    content: {
        contact: { phone: string; email: string; address: string };
        about: {
            title: string;
            paragraph_one: string;
            paragraph_two: string;
            mission: string;
        };
        values: { title: string; description: string }[];
        services: {
            title: string;
            intro: string;
            items: { title: string; description: string }[];
        };
    };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Página de inicio', href: edit() },
];

const confirmingReset = ref(false);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Página de inicio" />

        <h1 class="sr-only">Página de inicio</h1>

        <SettingsLayout>
            <div class="space-y-8">
                <Form
                    v-bind="LandingContentController.update.form()"
                    :options="{ preserveScroll: true }"
                    class="space-y-10"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <section class="space-y-4">
                        <Heading
                            variant="small"
                            title="Datos de contacto"
                            description="Se muestran al pie de la página de inicio. Deja vacío el que no quieras publicar."
                        />
                        <div class="grid gap-2">
                            <Label for="contact-phone">Teléfono</Label>
                            <Input
                                id="contact-phone"
                                name="contact[phone]"
                                :default-value="content.contact.phone"
                            />
                            <InputError :message="errors['contact.phone']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="contact-email">Correo</Label>
                            <Input
                                id="contact-email"
                                type="email"
                                name="contact[email]"
                                :default-value="content.contact.email"
                            />
                            <InputError :message="errors['contact.email']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="contact-address">Dirección</Label>
                            <Input
                                id="contact-address"
                                name="contact[address]"
                                :default-value="content.contact.address"
                            />
                            <InputError :message="errors['contact.address']" />
                        </div>
                    </section>

                    <section class="space-y-4 border-t pt-8">
                        <Heading
                            variant="small"
                            title="Quiénes somos"
                            description="Textos de la sección de presentación de la clínica."
                        />
                        <div class="grid gap-2">
                            <Label for="about-title">Título</Label>
                            <Input
                                id="about-title"
                                name="about[title]"
                                :default-value="content.about.title"
                            />
                            <InputError :message="errors['about.title']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="about-p1">Primer párrafo</Label>
                            <Textarea
                                id="about-p1"
                                name="about[paragraph_one]"
                                rows="4"
                                :default-value="content.about.paragraph_one"
                            />
                            <InputError
                                :message="errors['about.paragraph_one']"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="about-p2">Segundo párrafo</Label>
                            <Textarea
                                id="about-p2"
                                name="about[paragraph_two]"
                                rows="4"
                                :default-value="content.about.paragraph_two"
                            />
                            <InputError
                                :message="errors['about.paragraph_two']"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="about-mission">Nuestra misión</Label>
                            <Textarea
                                id="about-mission"
                                name="about[mission]"
                                rows="3"
                                :default-value="content.about.mission"
                            />
                            <InputError :message="errors['about.mission']" />
                        </div>

                        <div
                            v-for="(value, index) in content.values"
                            :key="index"
                            class="grid gap-2 rounded-lg border p-4"
                        >
                            <Label :for="`value-title-${index}`"
                                >Valor {{ index + 1 }}</Label
                            >
                            <Input
                                :id="`value-title-${index}`"
                                :name="`values[${index}][title]`"
                                :default-value="value.title"
                            />
                            <InputError
                                :message="errors[`values.${index}.title`]"
                            />
                            <Textarea
                                :name="`values[${index}][description]`"
                                rows="2"
                                :default-value="value.description"
                                :aria-label="`Descripción del valor ${index + 1}`"
                            />
                            <InputError
                                :message="errors[`values.${index}.description`]"
                            />
                        </div>
                    </section>

                    <section class="space-y-4 border-t pt-8">
                        <Heading
                            variant="small"
                            title="Servicios médicos"
                            description="Textos de la sección de servicios."
                        />
                        <div class="grid gap-2">
                            <Label for="services-title">Título</Label>
                            <Input
                                id="services-title"
                                name="services[title]"
                                :default-value="content.services.title"
                            />
                            <InputError :message="errors['services.title']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="services-intro">Introducción</Label>
                            <Textarea
                                id="services-intro"
                                name="services[intro]"
                                rows="3"
                                :default-value="content.services.intro"
                            />
                            <InputError :message="errors['services.intro']" />
                        </div>

                        <div
                            v-for="(service, index) in content.services.items"
                            :key="index"
                            class="grid gap-2 rounded-lg border p-4"
                        >
                            <Label :for="`service-title-${index}`"
                                >Servicio {{ index + 1 }}</Label
                            >
                            <Input
                                :id="`service-title-${index}`"
                                :name="`services[items][${index}][title]`"
                                :default-value="service.title"
                            />
                            <InputError
                                :message="
                                    errors[`services.items.${index}.title`]
                                "
                            />
                            <Textarea
                                :name="`services[items][${index}][description]`"
                                rows="2"
                                :default-value="service.description"
                                :aria-label="`Descripción del servicio ${index + 1}`"
                            />
                            <InputError
                                :message="
                                    errors[
                                        `services.items.${index}.description`
                                    ]
                                "
                            />
                        </div>
                    </section>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing">Guardar</Button>
                        <p
                            v-show="recentlySuccessful"
                            class="text-sm text-neutral-600"
                        >
                            Guardado.
                        </p>
                    </div>
                </Form>

                <div class="space-y-3 border-t pt-8">
                    <p class="text-sm text-muted-foreground">
                        ¿Quieres volver a los textos originales de la página?
                    </p>
                    <Button
                        v-if="!confirmingReset"
                        variant="outline"
                        @click="confirmingReset = true"
                    >
                        Restablecer textos
                    </Button>
                    <Form
                        v-else
                        v-bind="LandingContentController.destroy.form()"
                        :options="{ preserveScroll: true }"
                        class="flex gap-2"
                        v-slot="{ processing }"
                        @success="confirmingReset = false"
                    >
                        <Button variant="destructive" :disabled="processing"
                            >Sí, restablecer</Button
                        >
                        <Button
                            type="button"
                            variant="ghost"
                            @click="confirmingReset = false"
                            >Cancelar</Button
                        >
                    </Form>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
