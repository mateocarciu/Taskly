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

    const loadPage = async (
        pageNumber: number,
        append: boolean,
    ): Promise<boolean> => {
        if (isLoadingMore.value) return false;

        isLoadingMore.value = true;
        try {
            const response = await fetch(options.urlForPage(pageNumber), {
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

            if (append) {
                items.value.push(...data);
            } else {
                items.value = [...data];
            }

            if (nextPagination) {
                pagination.value = { ...nextPagination };
            } else if (meta) {
                pagination.value = toPaginationMeta(meta);
            }

            return true;
        } catch {
            toast.error(options.errorMessage);
            return false;
        } finally {
            isLoadingMore.value = false;
        }
    };

    sync();

    watch(source, sync, { deep: true });

    const loadFirstPage = () => loadPage(1, false);

    const loadMore = async () => {
        if (!pagination.value?.has_more) return;

        await loadPage(pagination.value.current_page + 1, true);
    };

    return { items, pagination, isLoadingMore, loadFirstPage, loadMore };
}
