import type { PreviewData } from '@/types';

// Client-side batch cache to avoid fetching the same link twice in the same session
const cache = new Map<string, PreviewData>();

const queue = new Set<string>();

type PromiseCallbacks = {
    resolve: (data: PreviewData) => void;
    reject: (err: any) => void;
};
const pending = new Map<string, PromiseCallbacks[]>();

let flushTimeoutId: ReturnType<typeof setTimeout> | null = null;

const flushQueue = async () => {
    flushTimeoutId = null;

    const urlsToFetch = Array.from(queue);
    queue.clear();

    if (urlsToFetch.length === 0) return;

    try {
        const params = new URLSearchParams();
        urlsToFetch.forEach((url) => {
            params.append('urls[]', url);
        });

        const response = await fetch(`/link-previews/batch?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Batch request failed: HTTP ${response.status}`);
        }

        const data: Record<string, PreviewData> = await response.json();

        urlsToFetch.forEach((url) => {
            const callbacks = pending.get(url) || [];
            pending.delete(url);

            const result = data[url];
            if (result) {
                cache.set(url, result);
                callbacks.forEach(({ resolve }) => resolve(result));
            } else {
                callbacks.forEach(({ reject }) => reject(new Error('No preview returned')));
            }
        });
    } catch (err) {
        urlsToFetch.forEach((url) => {
            const callbacks = pending.get(url) || [];
            pending.delete(url);
            callbacks.forEach(({ reject }) => reject(err));
        });
    }
};

const scheduleFlush = () => {
    if (flushTimeoutId !== null) return;
    flushTimeoutId = setTimeout(flushQueue, 50);
};

export const fetchPreview = (url: string): Promise<PreviewData> => {
    const cached = cache.get(url);
    if (cached) {
        return Promise.resolve(cached);
    }

    return new Promise<PreviewData>((resolve, reject) => {
        const callbacks = pending.get(url) || [];
        callbacks.push({ resolve, reject });
        pending.set(url, callbacks);

        queue.add(url);
        scheduleFlush();
    });
};
