import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(
    urlToCheck: NonNullable<InertiaLinkProps['href']>,
    currentUrl: string,
) {
    const cleanUrlToCheck = toUrl(urlToCheck).split(/[?#]/)[0];
    const cleanCurrentUrl = currentUrl.split(/[?#]/)[0];
    return cleanUrlToCheck === cleanCurrentUrl;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function capitalize(value: string) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

export const MIN_LOADING_DURATION_MS = 500;

export function ensureMinimumDelay(
    startedAt: number,
    minimumMs = MIN_LOADING_DURATION_MS,
) {
    const remaining = Math.max(0, minimumMs - (Date.now() - startedAt));

    return remaining > 0
        ? new Promise<void>((resolve) => setTimeout(resolve, remaining))
        : Promise.resolve();
}
