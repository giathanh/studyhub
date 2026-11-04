import '@inertiajs/core';

export interface AuthUser {
    id: number;
    name: string;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        flashDataType: {
            success?: string;
        };
        sharedPageProps: {
            auth: {
                user: AuthUser | null;
            };
        };
    }
}
