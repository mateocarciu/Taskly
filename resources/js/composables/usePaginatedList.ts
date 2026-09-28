import type { PaginationMeta } from '@/types';
import { ref, watch, type Ref } from 'vue';
import { toast } from 'vue-sonner';

interface PaginatedSource<T> {
    items: T[];
    pagination?: PaginationMeta;
}

interface UsePaginatedListOptions {
    urlForPage: (page: number) => string;
    errorMessage: string;
    preserveLoadedItems?: boolean;
}

type PaginatedPage<T> = {
    data: T[];
    pagination?: PaginationMeta;
    meta?: LaravelMeta;
};

interface LaravelMeta {
    current_page: number;
    last_page: number;
    total: number;
}

const toPaginationMeta = (meta: LaravelMeta): PaginationMeta => ({
    current_page: meta.current_page,
    last_page: meta.last_page,
    total: meta.total,
    has_more: meta.current_page < meta.last_page,
});

/**
 * Keeps a local copy of a server-paginated list and appends the next page on
 * demand, so a component can show more items without a full page reload.
 *
 * Accepts both the custom `pagination` payload and a plain Laravel paginator
 * `meta` object. The list re-syncs when the source changes (e.g. after an
 * Inertia visit), which resets it back to the first page.
 */
export function usePaginatedList<T extends { id: string | number }>(
    source: () => PaginatedSource<T>,
    options: UsePaginatedListOptions,
) {
    const items = ref([]) as Ref<T[]>;
    const pagination = ref<PaginationMeta>();
    const isLoadingMore = ref(false);

    const sync = () => {
        const { items: sourceItems, pagination: sourcePagination } = source();
        const currentPagination = pagination.value;
        const shouldPreserveLoadedItems =
            options.preserveLoadedItems &&
            currentPagination !== undefined &&
            currentPagination.current_page > 1 &&
            sourcePagination?.current_page === 1;

        if (shouldPreserveLoadedItems) {
            const sourceById = new Map(
                sourceItems.map((item) => [item.id, item]),
            );

            items.value = [
                ...sourceItems,
                ...items.value.filter((item) => !sourceById.has(item.id)),
            ];
            pagination.value = sourcePagination
                ? {
                      ...sourcePagination,
                      current_page: currentPagination.current_page,
                      has_more:
                          currentPagination.current_page <
                          sourcePagination.last_page,
                  }
                : currentPagination;

            return;
        }

        items.value = [...(sourceItems || [])];
        pagination.value = sourcePagination
            ? { ...sourcePagination }
            : undefined;
    };

    sync();

    watch(source, sync, { deep: true });

    const loadMore = async () => {
        if (!pagination.value?.has_more || isLoadingMore.value) return;

        isLoadingMore.value = true;
        try {
            const nextPage = pagination.value.current_page + 1;
            const response = await fetch(options.urlForPage(nextPage), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Request failed');

            const {
                data,
                pagination: nextPagination,
                meta,
            }: PaginatedPage<T> = await response.json();

            items.value.push(...data);
            if (nextPagination) {
                pagination.value = { ...nextPagination };
            } else if (meta) {
                pagination.value = toPaginationMeta(meta);
            }
        } catch {
            toast.error(options.errorMessage);
        } finally {
            isLoadingMore.value = false;
        }
    };

    return { items, pagination, isLoadingMore, loadMore };
}
