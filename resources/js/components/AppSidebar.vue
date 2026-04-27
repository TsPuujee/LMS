<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    BookOpen,
    BookUp,
    History,
    LayoutGrid,
    Tags,
    Users,
} from '@lucide/vue';
import BookController from '@/actions/App/Http/Controllers/BookController';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
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
import type { NavItem } from '@/types';

const loanNavItems: NavItem[] = [
    {
        title: 'Хянах самбар',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Ном зээлүүлэх',
        href: LoanController.create(),
        icon: BookUp,
    },
    {
        title: 'Хэнд ямар ном байна',
        href: LoanController.index(),
        icon: ArrowLeftRight,
    },
    {
        title: 'Зээлийн түүх',
        href: LoanController.history(),
        icon: History,
    },
];

const catalogNavItems: NavItem[] = [
    {
        title: 'Ном',
        href: BookController.index(),
        icon: BookOpen,
        activePrefix: '/books/',
    },
    {
        title: 'Ангилал',
        href: CategoryController.index(),
        icon: Tags,
    },
    {
        title: 'Уншигчид',
        href: MemberController.index(),
        icon: Users,
        activePrefix: '/members/',
    },
];
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
            <NavMain label="Зээл" :items="loanNavItems" />
            <NavMain label="Каталог" :items="catalogNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
