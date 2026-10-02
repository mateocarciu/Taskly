import { onMounted, onUnmounted, ref, type Ref } from 'vue';

/**
 * Load an external script tag idempotently and clean up on unmount.
 *
 * Returns reactive `isLoading` and `error` refs so callers can render
 * loading / error states in their template.
 *
 * @example
 * ```ts
 * const { isLoading, error } = useExternalScript(
 *   `https://${domain}/external_api.js`,
 *   () => window.JitsiMeetExternalAPI,
 * );
 * ```
 */
export function useExternalScript(
    src: string,
    isAlreadyLoaded?: () => unknown,
): { isLoading: Ref<boolean>; error: Ref<Error | null> } {
    const isLoading = ref(true);
    const error = ref<Error | null>(null);
    let scriptEl: HTMLScriptElement | null = null;

    onMounted(() => {
        if (isAlreadyLoaded?.()) {
            isLoading.value = false;
            return;
        }

        scriptEl = document.createElement('script');
        scriptEl.src = src;
        scriptEl.async = true;

        scriptEl.onload = () => {
            isLoading.value = false;
        };

        scriptEl.onerror = () => {
            error.value = new Error(`Failed to load script: ${src}`);
            isLoading.value = false;
        };

        document.head.appendChild(scriptEl);
    });

    onUnmounted(() => {
        scriptEl?.remove();
    });

    return { isLoading, error };
}
