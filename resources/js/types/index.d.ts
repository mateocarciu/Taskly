import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    currentTeam?: Team | null;
    teams?: Team[];
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    team_id: number | null;
    team?: Team;
}

export interface Task {
    id: number;
    team_id: number;
    column_id: number | null;
    order: number;
    title: string;
    description?: string | null;
    days_in_column: number;
    due_date: string;
    created_by: number;
    assigned_to?: number | null;
    creator?: {
        id: number;
        name: string;
    };
    assignee?: {
        id: number;
        name: string;
    };
    comments?: TaskComment[];
    events?: TaskEvent[];
}

export interface Column {
    id: number;
    team_id: number;
    name: string;
    order: number;
    tasks: Task[];
    pagination?: {
        current_page: number;
        last_page: number;
        total: number;
        has_more: boolean;
    };
}

export interface TaskForm {
    title: string;
    description: string;
    due_date: string;
    assigned_to: number | null;
}

export interface TeamMember {
    id: number;
    name: string;
}

export interface TaskComment {
    id: number;
    body: string;
    parent_id?: number | null;
    created_at: string;
    user: {
        id: number;
        name: string;
    };
    replies?: TaskComment[];
}

export interface TaskEvent {
    id: number;
    type: 'created' | 'moved' | 'assigned' | string;
    created_at: string;
    actor?: {
        id: number;
        name: string;
    } | null;
    metadata?: Record<string, unknown>;
}

export interface Team {
    id: number;
    name: string;
    count_days_in_column_tasks?: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
}

export interface PaginatedTasks {
    current_page: number;
    data: Task[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
}

export interface TaskEditFormState {
    title: string;
    description: string;
    due_date: string;
    assigned_to: number | null;
    errors: Record<string, string | undefined>;
    isDirty: boolean;
    processing: boolean;
}

export type BreadcrumbItemType = BreadcrumbItem;
