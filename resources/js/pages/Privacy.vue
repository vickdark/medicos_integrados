<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, HeartPulse } from 'lucide-vue-next';
import { computed } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { home } from '@/routes';

const props = defineProps<{
    version: string;
    updatedAt: string;
    controller: {
        company: string;
        nit: string | null;
        address: string | null;
        phone: string | null;
        email: string;
    };
}>();

type Section = { title: string; paragraphs?: string[]; items?: string[] };

const controllerDetails = computed(() =>
    [
        props.controller.nit ? `NIT: ${props.controller.nit}` : null,
        props.controller.address
            ? `Dirección: ${props.controller.address}`
            : null,
        props.controller.phone ? `Teléfono: ${props.controller.phone}` : null,
        `Correo electrónico: ${props.controller.email}`,
    ].filter((detail): detail is string => detail !== null),
);

const sections = computed<Section[]>(() => [
    {
        title: '1. Marco legal',
        paragraphs: [
            'Esta política se expide en cumplimiento del artículo 15 de la Constitución Política de Colombia, la Ley 1581 de 2012 (régimen general de protección de datos personales), el Decreto 1377 de 2013, compilado en el Decreto 1074 de 2015, y las demás normas que los modifiquen o complementen. Para la historia clínica aplican además la Ley 23 de 1981, la Resolución 1995 de 1999 y la Resolución 839 de 2017 del Ministerio de Salud y Protección Social.',
        ],
    },
    {
        title: '2. Responsable del tratamiento',
        paragraphs: [
            `${props.controller.company} es la responsable del tratamiento de los datos personales que se recolectan a través de este sistema.`,
        ],
        items: controllerDetails.value,
    },
    {
        title: '3. Datos que recolectamos',
        items: [
            'Datos de identificación y contacto: nombre, tipo y número de documento, correo electrónico, teléfono y dirección.',
            'Datos sensibles relativos a la salud: alergias, condiciones crónicas, signos vitales, diagnósticos, tratamientos, recetas y archivos clínicos adjuntos de tu historia clínica.',
            'Datos de citas y pagos: fechas, médico, motivo de la cita, montos y comprobantes.',
            'Datos técnicos de seguridad: dirección IP y registros de acceso a la historia clínica.',
        ],
    },
    {
        title: '4. Datos sensibles',
        paragraphs: [
            'Los datos relativos a tu salud son datos sensibles: su uso indebido puede afectar tu intimidad o generar discriminación. Por eso no estás obligado a autorizar su tratamiento, salvo cuando sea necesario para prestarte el servicio de salud que solicitas o cuando la ley lo exija, y en ese caso los protegemos con medidas reforzadas.',
            'Los datos de niños, niñas y adolescentes solo se tratan atendiendo al interés superior del menor y con autorización de sus padres o representantes legales.',
        ],
    },
    {
        title: '5. Finalidades del tratamiento',
        items: [
            'Crear y administrar tu cuenta y tu perfil de paciente.',
            'Programar, confirmar, reprogramar y recordarte tus citas.',
            'Prestar la atención médica y mantener tu historia clínica, con las recetas y órdenes que se generen.',
            'Registrar, cobrar y conciliar los pagos de tus consultas y emitir comprobantes.',
            'Enviarte comunicaciones del servicio por correo electrónico.',
            'Garantizar la seguridad del sistema y auditar el acceso a la información clínica.',
            'Cumplir obligaciones legales y atender requerimientos de autoridades competentes.',
        ],
    },
    {
        title: '6. Autorización',
        paragraphs: [
            'Al crear tu cuenta y marcar la casilla de aceptación nos das tu autorización previa, expresa e informada para tratar tus datos personales, incluidos los sensibles de salud, según esta política. Dejamos constancia de la fecha y de la versión que aceptaste, y puedes solicitar copia de esa prueba de autorización.',
        ],
    },
    {
        title: '7. Quién puede ver tu información',
        paragraphs: [
            'Solo accede a tu información el personal autorizado según su función: los médicos que te atienden ven tu historia clínica, recepción gestiona citas y pagos, y la administración supervisa el sistema. La historia clínica es reservada y cada acceso queda registrado.',
            'No vendemos tus datos. Solo los compartimos con proveedores que nos prestan servicios tecnológicos (como el envío de correos) bajo deberes de confidencialidad, o con autoridades cuando la ley lo exija.',
        ],
    },
    {
        title: '8. Tus derechos como titular',
        items: [
            'Conocer, actualizar y rectificar tus datos personales.',
            'Solicitar prueba de la autorización otorgada.',
            'Ser informado sobre el uso que se ha dado a tus datos.',
            'Presentar quejas ante la Superintendencia de Industria y Comercio (SIC) por infracciones a la normativa de protección de datos.',
            'Revocar la autorización y solicitar la supresión de tus datos cuando no se respeten los principios y garantías legales, salvo que exista un deber legal o contractual de conservarlos, como ocurre con la historia clínica.',
            'Acceder de forma gratuita a tus datos personales que hayan sido objeto de tratamiento.',
        ],
    },
    {
        title: '9. Cómo ejercer tus derechos',
        paragraphs: [
            `Puedes presentar consultas y reclamos escribiendo a ${props.controller.email}, indicando tu nombre, tu número de documento y lo que solicitas. Las consultas se responden en un máximo de diez (10) días hábiles y los reclamos en un máximo de quince (15) días hábiles, contados desde el día siguiente a su recibo. Si no es posible responder en ese plazo, te informaremos los motivos y la nueva fecha, que no superará cinco (5) días hábiles adicionales para las consultas ni ocho (8) para los reclamos.`,
        ],
    },
    {
        title: '10. Conservación',
        paragraphs: [
            'La historia clínica se conserva por el tiempo que exige la normativa sanitaria colombiana (actualmente veinte años contados desde la última atención). El resto de tus datos se conservan mientras mantengas tu cuenta o sea necesario para cumplir obligaciones legales; después se suprimen o anonimizan.',
        ],
    },
    {
        title: '11. Seguridad',
        paragraphs: [
            'Aplicamos medidas técnicas y administrativas razonables: control de acceso por rol, contraseñas protegidas, autenticación de dos factores opcional, archivos clínicos en almacenamiento privado y registro de auditoría.',
        ],
    },
    {
        title: '12. Vigencia y cambios',
        paragraphs: [
            'Esta política rige desde su fecha de publicación. Si hacemos cambios sustanciales te lo comunicaremos y actualizaremos su versión y fecha.',
        ],
    },
]);
</script>

<template>
    <Head title="Política de tratamiento de datos personales" />

    <div class="min-h-svh bg-background">
        <header class="border-b">
            <div
                class="mx-auto flex h-14 max-w-3xl items-center justify-between px-4"
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

        <main class="mx-auto max-w-3xl px-4 py-10">
            <h1 class="text-3xl font-semibold tracking-tight">
                Política de tratamiento de datos personales
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Versión {{ version }} · Actualizada el
                {{ formatDate(updatedAt) }}
            </p>

            <div class="mt-8 space-y-8">
                <section v-for="section in sections" :key="section.title">
                    <h2 class="text-lg font-semibold">{{ section.title }}</h2>
                    <p
                        v-for="paragraph in section.paragraphs"
                        :key="paragraph"
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ paragraph }}
                    </p>
                    <ul
                        v-if="section.items"
                        class="mt-2 list-disc space-y-1 pl-5 text-sm leading-relaxed text-muted-foreground"
                    >
                        <li v-for="item in section.items" :key="item">
                            {{ item }}
                        </li>
                    </ul>
                </section>
            </div>

            <Button variant="outline" class="mt-10" as-child>
                <Link :href="home()"><ArrowLeft /> Volver al inicio</Link>
            </Button>
        </main>
    </div>
</template>
