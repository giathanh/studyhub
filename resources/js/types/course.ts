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
    completed_lessons_count?: number;
    progress?: number;
}

export interface Lesson {
    id: number;
    course_id?: number;
    section: string | null;
    title: string;
    status: string;
    status_label: string;
    position?: number;
    completed_at?: string | null;
    content?: string | null;
}

export interface CourseOption {
    id: number;
    title: string;
    lessons: {
        id: number;
        title: string;
    }[];
}

export interface Note {
    id: number;
    title: string;
    excerpt: string;
    body?: string;
    course_id: number | null;
    lesson_id: number | null;
    course?: {
        id: number;
        title: string;
    } | null;
    lesson?: {
        id: number;
        course_id: number;
        title: string;
    } | null;
    updated_at: string | null;
}

export interface NoteFilters {
    search: string;
    course_id: number | null;
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
