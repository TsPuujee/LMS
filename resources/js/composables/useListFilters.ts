import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { reactive, watch } from 'vue';

type FilterValue = string | number | null | undefined;

/**
 * Keeps list filters (search box, selects) in the query string. Typing in
 * `search` is debounced; other fields reload immediately. Changing any filter
 * goes back to page 1.
 */
export function useListFilters<T extends Record<string, FilterValue>>(
    initial: T,
    /** Value each field takes when the filters are cleared (default ''). */
    empty: Partial<T> = {},
) {
    const filters = reactive({ ...initial }) as T;

    const query = () =>
        Object.fromEntries(
            Object.entries(filters).filter(
                ([, value]) =>
                    value !== '' &&
                    value !== null &&
                    value !== undefined &&
                    value !== 'all',
            ),
        );

    const apply = () =>
        router.get(window.location.pathname, query(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });

    const applyDebounced = useDebounceFn(apply, 300);

    watch(
        () => ({ ...filters }),
        (next, prev) => {
            if (next.search !== prev.search) {
                void applyDebounced();
            } else {
                apply();
            }
        },
    );

    const reset = () => {
        for (const key of Object.keys(filters)) {
            (filters as Record<string, FilterValue>)[key] = empty[key] ?? '';
        }
    };

    const isFiltered = () => Object.keys(query()).length > 0;

    return { filters, reset, isFiltered };
}
