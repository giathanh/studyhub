export interface StatusOption {
    value: string;
    label: string;
}

export interface Course {
    id: number;
    title: string;
    description: string | null;
    status: string;
    status_label: string;
    target_date: string | null;
    lessons_count?: number;
}

export interface Lesson {
    id: number;
    section: string | null;
    title: string;
    status: string;
    status_label: string;
    content?: string | null;
}

export interface Paginated<T> {
    data: T[];
    links: {
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        total: number;
    };
}
