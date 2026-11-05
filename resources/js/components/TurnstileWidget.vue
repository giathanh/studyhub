<script setup lang="ts">
import { onMounted, onUnmounted, ref, useTemplateRef } from 'vue';

const props = defineProps<{
    siteKey: string;
    error?: string;
}>();

const scriptUrl = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

const container = useTemplateRef<HTMLDivElement>('container');
const token = ref('');
const isUnavailable = ref(false);

let widgetId: string | undefined;
let isUnmounted = false;

function loadScript(): Promise<void> {
    if (window.turnstile) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        let script = document.querySelector<HTMLScriptElement>(`script[src="${scriptUrl}"]`);

        if (!script) {
            script = document.createElement('script');
            script.src = scriptUrl;
            document.head.append(script);
        }

        const loadingScript = script;

        loadingScript.addEventListener('load', () => resolve(), { once: true });
        loadingScript.addEventListener(
            'error',
            () => {
                loadingScript.remove();
                reject(new Error('The Turnstile script failed to load.'));
            },
            { once: true },
        );
    });
}

function clearToken(): void {
    token.value = '';
}

function reset(): void {
    clearToken();

    if (widgetId) {
        window.turnstile?.reset(widgetId);
    }
}

onMounted(async () => {
    try {
        await loadScript();
    } catch {
        isUnavailable.value = true;

        return;
    }

    if (isUnmounted || !container.value) {
        return;
    }

    widgetId = window.turnstile?.render(container.value, {
        sitekey: props.siteKey,
        size: 'flexible',
        'response-field': false,
        callback: (newToken) => {
            token.value = newToken;
            isUnavailable.value = false;
        },
        'expired-callback': clearToken,
        'timeout-callback': clearToken,
        'error-callback': () => {
            clearToken();
            isUnavailable.value = true;
        },
    });
});

onUnmounted(() => {
    isUnmounted = true;

    if (widgetId) {
        window.turnstile?.remove(widgetId);
    }
});

defineExpose({ reset });
</script>

<template>
    <div>
        <input type="hidden" name="cf-turnstile-response" :value="token" />
        <div ref="container" />
        <p v-if="isUnavailable" class="mt-1 text-sm text-red-600">
            The security check could not load. Refresh the page and try again.
        </p>
        <p v-else-if="error" class="mt-1 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>
