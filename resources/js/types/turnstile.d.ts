interface TurnstileRenderOptions {
    sitekey: string;
    size?: 'normal' | 'flexible' | 'compact';
    'response-field'?: boolean;
    callback?: (token: string) => void;
    'expired-callback'?: () => void;
    'timeout-callback'?: () => void;
    'error-callback'?: () => void;
}

interface Window {
    turnstile?: {
        render(container: HTMLElement, options: TurnstileRenderOptions): string | undefined;
        reset(widgetId: string): void;
        remove(widgetId: string): void;
    };
}
