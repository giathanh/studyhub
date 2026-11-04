import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

export function useUnsavedChanges(shouldWarn: () => boolean): void {
    function handleBeforeUnload(event: BeforeUnloadEvent): void {
        if (shouldWarn()) {
            event.preventDefault();
        }
    }

    let removeRouterListener: (() => void) | null = null;

    onMounted(() => {
        window.addEventListener('beforeunload', handleBeforeUnload);
        removeRouterListener = router.on('before', (event) => {
            if (event.detail.visit.method !== 'get' || !shouldWarn()) {
                return;
            }

            if (!window.confirm('Discard unsaved changes and leave this page?')) {
                event.preventDefault();
            }
        });
    });

    onUnmounted(() => {
        window.removeEventListener('beforeunload', handleBeforeUnload);
        removeRouterListener?.();
    });
}
