<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    ClipboardList,
    Clock,
    LayoutGrid,
    ShieldCheck,
    Stethoscope,
    Tags,
    UserCog,
    Users,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import appointmentRoutes from '@/routes/appointments';
import auditLogRoutes from '@/routes/audit-logs';
import doctorRoutes from '@/routes/doctors';
import patientRoutes from '@/routes/patients';
import paymentRoutes from '@/routes/payments';
import scheduleRoutes from '@/routes/schedules';
import specialtyRoutes from '@/routes/specialties';
import userRoutes from '@/routes/users';
import type { NavItem } from '@/types';
import type { RoleValue } from '@/types/models';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const role = page.props.auth.role?.value as RoleValue | undefined;
    const patientId = page.props.auth.patientId;
    const doctorId = page.props.auth.doctorId;
    const home: NavItem = {
        title: 'Inicio',
        href: dashboard(),
        icon: LayoutGrid,
    };

    if (role === 'patient') {
        return [
            home,
            ...(patientId
                ? [
                      {
                          title: 'Mi historial',
                          href: patientRoutes.show(patientId),
                          icon: ClipboardList,
                      },
                  ]
                : []),
            {
                title: 'Mis citas',
                href: appointmentRoutes.index(),
                icon: CalendarDays,
            },
            { title: 'Mis pagos', href: paymentRoutes.index(), icon: Wallet },
        ];
    }

    const items: NavItem[] = [
        home,
        {
            title: role === 'doctor' ? 'Mis pacientes' : 'Pacientes',
            href: patientRoutes.index(),
            icon: Users,
        },
        {
            title: role === 'doctor' ? 'Mi agenda' : 'Citas',
            href: appointmentRoutes.index(),
            icon: CalendarDays,
        },
    ];

    if (role === 'admin' || role === 'receptionist') {
        items.push({
            title: 'Pagos',
            href: paymentRoutes.index(),
            icon: Wallet,
        });
    }

    if (role === 'doctor' && doctorId) {
        items.push({
            title: 'Mi horario',
            href: scheduleRoutes.index(doctorId),
            icon: Clock,
        });
    }

    if (role === 'admin') {
        items.push(
            {
                title: 'Usuarios',
                href: userRoutes.index(),
                icon: UserCog,
            },
            {
                title: 'Médicos',
                href: doctorRoutes.index(),
                icon: Stethoscope,
            },
            {
                title: 'Especialidades',
                href: specialtyRoutes.index(),
                icon: Tags,
            },
            {
                title: 'Auditoría',
                href: auditLogRoutes.index(),
                icon: ShieldCheck,
            },
        );
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
