<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    BadgeCheck,
    CalendarCheck,
    CircleCheck,
    ClipboardCheck,
    ClipboardList,
    FileText,
    HeartHandshake,
    HeartPulse,
    Laptop,
    Mail,
    MapPin,
    Phone,
    Pill,
    ShieldCheck,
    Stethoscope,
    Users,
    Wallet,
} from 'lucide-vue-next';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { dashboard, login, privacy, register } from '@/routes';

type LandingContent = {
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

withDefaults(
    defineProps<{
        canRegister: boolean;
        content: LandingContent;
        doctors?:
            | {
                  id: number;
                  name: string;
                  specialty: string;
                  bio: string | null;
                  photo_url: string | null;
              }[]
            | null;
    }>(),
    {
        canRegister: true,
        doctors: null,
    },
);

function initials(name: string): string {
    return name
        .split(' ')
        .filter((part) => part.length > 0 && !/^(dr|dra)\.?$/i.test(part))
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

const highlights = [
    'Historia clínica cifrada',
    'Citas sin llamadas',
    'Recetas y facturas en PDF',
];

const valueIcons = [HeartHandshake, ShieldCheck, Laptop];

const serviceIcons = [
    Stethoscope,
    Users,
    ClipboardCheck,
    Activity,
    Pill,
    FileText,
];

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
            'Solicita tu cita con el especialista que necesitas, elige el horario libre que prefieras y haz seguimiento de su estado.',
    },
    {
        icon: Wallet,
        title: 'Pagos claros',
        description:
            'Consulta tus pagos realizados y pendientes con el detalle de cada atención y descarga tu factura.',
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
            class="sticky top-0 z-30 border-b bg-background/80 backdrop-blur"
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
                    <a href="#nosotros" class="hover:text-foreground"
                        >Quiénes somos</a
                    >
                    <a href="#servicios-medicos" class="hover:text-foreground"
                        >Servicios médicos</a
                    >
                    <a href="#portal" class="hover:text-foreground"
                        >Tu portal</a
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
            <section class="relative isolate overflow-hidden">
                <div class="absolute inset-0 -z-10" aria-hidden="true">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-brand-50 via-background to-brand-100/60 dark:from-brand-950/60 dark:via-background dark:to-brand-900/20"
                    />
                    <div
                        class="absolute -top-32 -right-24 size-[34rem] rounded-full bg-brand-300/40 blur-3xl dark:bg-brand-500/15"
                    />
                    <div
                        class="absolute -bottom-40 -left-32 size-[30rem] rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-700/15"
                    />
                    <div
                        class="absolute inset-0 bg-[radial-gradient(var(--border)_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_center,black_30%,transparent_75%)] [background-size:22px_22px]"
                    />
                </div>

                <div
                    class="mx-auto grid max-w-6xl items-center gap-14 px-4 py-16 lg:grid-cols-[1.05fr_0.95fr] lg:py-28"
                >
                    <div class="space-y-8">
                        <p
                            class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white/80 px-3.5 py-1.5 text-xs font-medium text-brand-700 shadow-sm backdrop-blur dark:border-brand-800 dark:bg-brand-950/70 dark:text-brand-300"
                        >
                            <Stethoscope class="size-3.5" /> Centro médico
                            integral
                        </p>

                        <h1
                            class="text-4xl leading-[1.08] font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl"
                        >
                            Tu salud y tu historial médico,
                            <span
                                class="bg-gradient-to-r from-brand-700 to-brand-400 bg-clip-text text-transparent dark:from-brand-400 dark:to-brand-200"
                                >en un solo lugar</span
                            >
                        </h1>

                        <p class="max-w-xl text-lg text-muted-foreground">
                            Agenda citas con nuestros especialistas, consulta
                            tus diagnósticos y recetas, y lleva el control de
                            tus pagos desde cualquier dispositivo.
                        </p>

                        <div class="flex flex-wrap items-center gap-3">
                            <Link
                                :href="
                                    $page.props.auth.user
                                        ? dashboard()
                                        : canRegister
                                          ? register()
                                          : login()
                                "
                                class="group inline-flex items-center gap-2 rounded-full bg-brand-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700 hover:shadow-brand-600/35"
                            >
                                Solicitar una cita
                                <ArrowRight
                                    class="size-4 transition-transform group-hover:translate-x-0.5"
                                />
                            </Link>
                            <a
                                href="#como-funciona"
                                class="inline-flex items-center rounded-full border bg-background/70 px-6 py-3.5 text-sm font-semibold backdrop-blur transition hover:bg-accent"
                            >
                                Cómo funciona
                            </a>
                        </div>

                        <ul
                            class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground"
                        >
                            <li
                                v-for="highlight in highlights"
                                :key="highlight"
                                class="inline-flex items-center gap-1.5"
                            >
                                <CircleCheck
                                    class="size-4 text-brand-600 dark:text-brand-400"
                                />
                                {{ highlight }}
                            </li>
                        </ul>
                    </div>

                    <div
                        class="relative mx-auto w-full max-w-md pb-10 sm:max-w-lg lg:pb-0"
                        aria-hidden="true"
                    >
                        <div
                            class="absolute inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-brand-400/30 to-brand-600/10 blur-2xl"
                        />

                        <div
                            class="relative rounded-3xl border bg-card/95 p-6 shadow-2xl shadow-brand-900/10 backdrop-blur"
                        >
                            <div class="mb-5 flex items-center justify-between">
                                <p class="text-sm font-semibold">
                                    Próxima cita
                                </p>
                                <span
                                    class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-500/15 dark:text-sky-300"
                                    >Confirmada</span
                                >
                            </div>
                            <div class="flex items-center gap-4">
                                <span
                                    class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-lg font-semibold text-white"
                                >
                                    Dr
                                </span>
                                <div>
                                    <p class="text-2xl font-semibold">
                                        Jueves, 10:30 AM
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        Cardiología · Control general
                                    </p>
                                </div>
                            </div>
                            <div class="my-5 border-t" />
                            <p class="mb-3 text-sm font-semibold">
                                Última consulta
                            </p>
                            <div class="space-y-2.5 text-sm">
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

                        <div
                            class="absolute -bottom-2 -left-2 flex items-center gap-3 rounded-2xl border bg-card p-3.5 pr-5 shadow-xl sm:-left-8"
                        >
                            <span
                                class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                            >
                                <BadgeCheck class="size-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold">
                                    Pago confirmado
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Factura disponible
                                </p>
                            </div>
                        </div>

                        <div
                            class="absolute -top-4 -right-2 flex items-center gap-3 rounded-2xl border bg-card p-3.5 pr-5 shadow-xl sm:-right-6"
                        >
                            <span
                                class="flex size-10 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300"
                            >
                                <FileText class="size-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold">
                                    Receta lista
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Te la enviamos por correo
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="nosotros"
                class="mx-auto grid max-w-6xl scroll-mt-20 items-center gap-12 px-4 py-20 lg:grid-cols-2"
            >
                <div class="space-y-5">
                    <p
                        class="text-sm font-semibold tracking-wide text-brand-700 uppercase dark:text-brand-400"
                    >
                        Quiénes somos
                    </p>
                    <h2
                        class="text-3xl font-semibold tracking-tight text-balance sm:text-4xl"
                    >
                        {{ content.about.title }}
                    </h2>
                    <p class="text-muted-foreground">
                        {{ content.about.paragraph_one }}
                    </p>
                    <p class="text-muted-foreground">
                        {{ content.about.paragraph_two }}
                    </p>
                    <div
                        class="grid gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-800 dark:bg-brand-950/40"
                    >
                        <p
                            class="flex items-center gap-2 text-sm font-semibold text-brand-800 dark:text-brand-200"
                        >
                            <HeartPulse class="size-4" /> Nuestra misión
                        </p>
                        <p class="text-sm text-brand-900 dark:text-brand-100">
                            {{ content.about.mission }}
                        </p>
                    </div>
                </div>

                <ul class="grid gap-4">
                    <li
                        v-for="(value, index) in content.values"
                        :key="index"
                        class="flex gap-4 rounded-xl border bg-card p-5 transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white"
                        >
                            <component :is="valueIcons[index]" class="size-5" />
                        </span>
                        <div>
                            <h3 class="font-semibold">{{ value.title }}</h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ value.description }}
                            </p>
                        </div>
                    </li>
                </ul>

                <div v-if="doctors && doctors.length" class="lg:col-span-2">
                    <div class="mt-6 mb-8">
                        <h3 class="text-2xl font-semibold tracking-tight">
                            Nuestro equipo médico
                        </h3>
                        <p class="mt-1 text-muted-foreground">
                            Profesionales comprometidos con tu salud.
                        </p>
                    </div>
                    <div
                        class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <article
                            v-for="doctor in doctors"
                            :key="doctor.id"
                            class="group overflow-hidden rounded-2xl border bg-card shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                        >
                            <div
                                class="relative aspect-[4/4.2] overflow-hidden bg-gradient-to-br from-brand-100 to-brand-300 dark:from-brand-900 dark:to-brand-700"
                            >
                                <img
                                    v-if="doctor.photo_url"
                                    :src="doctor.photo_url"
                                    :alt="`Foto de ${doctor.name}`"
                                    loading="lazy"
                                    class="size-full object-cover transition duration-500 group-hover:scale-105"
                                />
                                <span
                                    v-else
                                    class="flex size-full items-center justify-center text-5xl font-semibold text-brand-700/70 dark:text-brand-200/70"
                                    aria-hidden="true"
                                >
                                    {{ initials(doctor.name) }}
                                </span>
                            </div>
                            <div class="space-y-2 p-5">
                                <h4 class="font-semibold">{{ doctor.name }}</h4>
                                <p
                                    class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800 dark:bg-brand-500/15 dark:text-brand-300"
                                >
                                    {{ doctor.specialty }}
                                </p>
                                <p
                                    v-if="doctor.bio"
                                    class="line-clamp-3 text-sm text-muted-foreground"
                                >
                                    {{ doctor.bio }}
                                </p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section
                id="servicios-medicos"
                class="scroll-mt-20 border-y bg-muted/30"
            >
                <div class="mx-auto max-w-6xl px-4 py-20">
                    <div class="mb-12 max-w-2xl">
                        <p
                            class="text-sm font-semibold tracking-wide text-brand-700 uppercase dark:text-brand-400"
                        >
                            Servicios médicos
                        </p>
                        <h2
                            class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl"
                        >
                            {{ content.services.title }}
                        </h2>
                        <p class="mt-3 text-muted-foreground">
                            {{ content.services.intro }}
                        </p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="(service, index) in content.services.items"
                            :key="index"
                            class="group rounded-xl border bg-card p-6 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md dark:hover:border-brand-700"
                        >
                            <span
                                class="mb-4 flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 transition group-hover:bg-brand-600 group-hover:text-white dark:bg-brand-950 dark:text-brand-300"
                            >
                                <component :is="serviceIcons[index]" class="size-5" />
                            </span>
                            <h3 class="font-semibold">{{ service.title }}</h3>
                            <p class="mt-2 text-sm text-muted-foreground">
                                {{ service.description }}
                            </p>
                        </article>
                    </div>
                    <div class="mt-10">
                        <Link
                            :href="
                                $page.props.auth.user
                                    ? dashboard()
                                    : canRegister
                                      ? register()
                                      : login()
                            "
                            class="group inline-flex items-center gap-2 rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700"
                        >
                            Agendar una cita
                            <ArrowRight
                                class="size-4 transition-transform group-hover:translate-x-0.5"
                            />
                        </Link>
                    </div>
                </div>
            </section>

            <section
                id="portal"
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
                        class="rounded-xl border bg-card p-6 transition hover:-translate-y-0.5 hover:shadow-md"
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
                id="como-funciona"
                class="scroll-mt-20 border-y bg-muted/30"
            >
                <div class="mx-auto max-w-6xl px-4 py-20">
                    <h2 class="mb-10 text-3xl font-semibold tracking-tight">
                        Cómo funciona
                    </h2>
                    <ol class="grid gap-6 md:grid-cols-3">
                        <li
                            v-for="(step, index) in steps"
                            :key="step.title"
                            class="rounded-xl border bg-card p-6"
                        >
                            <span
                                class="flex size-8 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white"
                                >{{ index + 1 }}</span
                            >
                            <h3 class="mt-4 text-lg font-semibold">
                                {{ step.title }}
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ step.description }}
                            </p>
                        </li>
                    </ol>
                </div>
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
                    <li
                        v-if="content.contact.phone"
                        class="flex items-center gap-2"
                    >
                        <Phone class="size-4" /> {{ content.contact.phone }}
                    </li>
                    <li
                        v-if="content.contact.email"
                        class="flex items-center gap-2"
                    >
                        <Mail class="size-4" /> {{ content.contact.email }}
                    </li>
                    <li
                        v-if="content.contact.address"
                        class="flex items-center gap-2"
                    >
                        <MapPin class="size-4" /> {{ content.contact.address }}
                    </li>
                </ul>
                <div class="text-sm text-muted-foreground sm:text-right">
                    <p>
                        © {{ new Date().getFullYear() }} Médicos Integrados
                    </p>
                    <Link
                        :href="privacy()"
                        class="mt-2 inline-block underline-offset-4 hover:underline"
                    >
                        Política de tratamiento de datos personales
                    </Link>
                </div>
            </div>
        </footer>
    </div>
</template>
