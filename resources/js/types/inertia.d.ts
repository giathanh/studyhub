import '@inertiajs/core';

export interface AuthUser {
    id: number;
    name: string;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            auth: {
                user: AuthUser | null;
            };
        };
    }
}
