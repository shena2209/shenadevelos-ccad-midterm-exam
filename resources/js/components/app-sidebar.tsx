import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, CalendarCheck, ClipboardList, GraduationCap, Layers3, LayoutGrid, UsersRound } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Students',
        url: '/school-records/students',
        icon: UsersRound,
    },
    {
        title: 'Teachers',
        url: '/school-records/teachers',
        icon: GraduationCap,
    },
    {
        title: 'Subjects',
        url: '/school-records/subjects',
        icon: BookOpen,
    },
    {
        title: 'Sections',
        url: '/school-records/sections',
        icon: Layers3,
    },
    {
        title: 'Attendance',
        url: '/attendance',
        icon: CalendarCheck,
    },
    {
        title: 'Grade records',
        url: '/dashboard#recent-grades',
        icon: ClipboardList,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
