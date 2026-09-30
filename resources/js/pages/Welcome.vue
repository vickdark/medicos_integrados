<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarCheck,
    ClipboardList,
    HeartPulse,
    Mail,
    MapPin,
    Phone,
    ShieldCheck,
    Stethoscope,
    Wallet,
} from 'lucide-vue-next';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
        specialties: {
            id: number;
            name: string;
            description: string | null;
            doctors_count: number;
        }[];
    }>(),
    {
        canRegister: true,
        specialties: () => [],
    },
);

const features = [
    {
        icon: ClipboardList,
        title: 'Historia clínica digital',
        description:
            'Consultas, diagnósticos, recetas y antecedentes en un solo lugar, siempre disponibles para ti y tu médico.',
    },
    {
        icon: CalendarCheck,
        title: 'Citas en línea',
        description:
            'Solicita tu cita con el especialista que necesitas y haz seguimiento de su estado sin llamadas.',
    },
    {
        icon: Wallet,
        title: 'Pagos claros',
        description:
            'Consulta tus pagos realizados y pendientes con el detalle de cada atención.',
    },
    {
        icon: ShieldCheck,
        title: 'Información protegida',
        description:
            'Tus datos clínicos se guardan cifrados y solo los ve el personal autorizado.',
    },
];

const steps = [
    {
        title: 'Crea tu cuenta',
        description: 'Regístrate con tu correo en menos de un minuto.',
    },
    {
        title: 'Solicita tu cita',
        description: 'Elige al médico, la fecha y cuéntanos el motivo.',
    },
    {
        title: 'Sigue tu atención',
        description: 'Revisa la confirmación, tu historial y tus recetas.',
    },
];
</script>

<template>
    <Head title="Tu salud en un solo lugar" />

    <div class="min-h-screen bg-background text-foreground">
        <header
            class="sticky top-0 z-20 border-b bg-background/80 backdrop-blur"
        >
            <nav
                class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3"
            >
                <Link href="/" class="flex items-center gap-2 font-semibold">
                    <span
                        class="flex size-8 items-center justify-center rounded-md bg-brand-600 text-white"
                    >
                        <HeartPulse class="size-5" />
                    </span>
                    Médicos Integrados
                </Link>
                <div
                    class="hidden items-center gap-6 text-sm text-muted-foreground md:flex"
                >
                    <a href="#servicios" class="hover:text-foreground"
                        >Servicios</a
                    >
                    <a href="#especialidades" class="hover:text-foreground"
                        >Especialidades</a
                    >
                    <a href="#como-funciona" class="hover:text-foreground"
                        >Cómo funciona</a
                    >
                    <a href="#contacto" class="hover:text-foreground"
                        >Contacto</a
                    >
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <ThemeToggle />
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700"
                    >
                        Ir a mi panel
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-3 py-2 font-medium hover:bg-accent"
                        >
                            Iniciar sesión
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700"
                        >
                            Registrarse
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <main>
            <section class="relative overflow-hidden">
                <div
                    class="absolute inset-0 -z-10 bg-gradient-to-b from-brand-50 to-transparent dark:from-brand-950/40"
                    aria-hidden="true"
                />
                <div
                    class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 lg:grid-cols-2 lg:py-28"
                >
                    <div class="space-y-6">
                        <p
                            class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white px-3 py-1 text-xs font-medium text-brand-700 dark:border-brand-800 dark:bg-brand-950 dark:text-brand-300"
                        >
                            <Stethoscope class="size-3.5" /> Centro médico
                            integral
                        </p>
                        <h1
                            class="text-4xl font-semibold tracking-tight text-balance sm:text-5xl"
                        >
                            Tu salud y tu historial médico, en un solo lugar
                        </h1>
                        <p class="max-w-xl text-lg text-muted-foreground">
                            Agenda citas con nuestros especialistas, consulta
                            tus diagnósticos y recetas, y lleva el control de
                            tus pagos desde cualquier dispositivo.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <Link
                                :href="
                                    $page.props.auth.user
                                        ? dashboard()
                                        : canRegister
                                          ? register()
                                          : login()
                                "
                                class="rounded-md bg-brand-600 px-5 py-3 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
                            >
                                Solicitar una cita
                            </Link>
                            <a
                                href="#especialidades"
                                class="rounded-md border px-5 py-3 text-sm font-medium hover:bg-accent"
                            >
                                Ver especialidades
                            </a>
                        </div>
                    </div>

                    <div
                        class="relative mx-auto w-full max-w-md"
                        aria-hidden="true"
                    >
                        <div class="rounded-2xl border bg-card p-6 shadow-xl">
                            <div class="mb-4 flex items-center justify-between">
                                <p class="text-sm font-semibold">
                                    Próxima cita
                                </p>
                                <span
                                    class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-500/15 dark:text-sky-300"
                                    >Confirmada</span
                                >
                            </div>
                            <p class="text-2xl font-semibold">Jueves, 10:30</p>
                            <p class="text-sm text-muted-foreground">
                                Cardiología · Control general
                            </p>
                            <div class="my-5 border-t" />
                            <p class="mb-3 text-sm font-semibold">
                                Última consulta
                            </p>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Presión</span
                                    ><span class="font-medium">118/76</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Diagnóstico</span
                                    ><span class="font-medium"
                                        >Paciente sano</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Receta</span
                                    ><span class="font-medium"
                                        >Sin medicación</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="servicios"
                class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20"
            >
                <div class="mb-12 max-w-2xl">
                    <h2 class="text-3xl font-semibold tracking-tight">
                        Todo lo que necesitas para cuidar tu salud
                    </h2>
                    <p class="mt-3 text-muted-foreground">
                        Un portal para pacientes conectado con el trabajo diario
                        de nuestros médicos y personal administrativo.
                    </p>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-xl border bg-card p-6"
                    >
                        <span
                            class="mb-4 flex size-10 items-center justify-center rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300"
                        >
                            <component :is="feature.icon" class="size-5" />
                        </span>
                        <h3 class="font-semibold">{{ feature.title }}</h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                id="especialidades"
                class="scroll-mt-20 border-y bg-muted/30"
            >
                <div class="mx-auto max-w-6xl px-4 py-20">
                    <h2 class="mb-10 text-3xl font-semibold tracking-tight">
                        Nuestras especialidades
                    </h2>
                    <div
                        v-if="specialties.length"
                        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div
                            v-for="specialty in specialties"
                            :key="specialty.id"
                            class="rounded-xl border bg-card p-5"
                        >
                            <h3 class="font-semibold">{{ specialty.name }}</h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ specialty.description }}
                            </p>
                            <p
                                class="mt-3 text-xs font-medium text-brand-700 dark:text-brand-400"
                            >
                                {{ specialty.doctors_count }}
                                {{
                                    specialty.doctors_count === 1
                                        ? 'médico'
                                        : 'médicos'
                                }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground">
                        Pronto publicaremos nuestras especialidades.
                    </p>
                </div>
            </section>

            <section
                id="como-funciona"
                class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20"
            >
                <h2 class="mb-10 text-3xl font-semibold tracking-tight">
                    Cómo funciona
                </h2>
                <ol class="grid gap-6 md:grid-cols-3">
                    <li
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="rounded-xl border p-6"
                    >
                        <span
                            class="text-sm font-semibold text-brand-700 dark:text-brand-400"
                            >Paso {{ index + 1 }}</span
                        >
                        <h3 class="mt-2 text-lg font-semibold">
                            {{ step.title }}
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ step.description }}
                        </p>
                    </li>
                </ol>
            </section>
        </main>

        <footer id="contacto" class="scroll-mt-20 border-t bg-muted/30">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-3">
                <div>
                    <p class="flex items-center gap-2 font-semibold">
                        <HeartPulse class="size-5 text-brand-600" /> Médicos
                        Integrados
                    </p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Atención médica integral para toda la familia.
                    </p>
                </div>
                <ul class="space-y-2 text-sm text-muted-foreground">
                    <li class="flex items-center gap-2">
                        <Phone class="size-4" /> (01) 000-0000
                    </li>
                    <li class="flex items-center gap-2">
                        <Mail class="size-4" /> contacto@medicosintegrados.test
                    </li>
                    <li class="flex items-center gap-2">
                        <MapPin class="size-4" /> Dirección de la clínica
                    </li>
                </ul>
                <p class="text-sm text-muted-foreground sm:text-right">
                    © {{ new Date().getFullYear() }} Médicos Integrados
                </p>
            </div>
        </footer>
    </div>
</template>
