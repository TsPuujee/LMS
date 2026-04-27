<script setup lang="ts" generic="T extends { id: number }">
import { useDebounceFn } from '@vueuse/core';
import { Search, X } from '@lucide/vue';
import { nextTick, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

/**
 * Search-as-you-type picker backed by a JSON endpoint.
 * The selected item is shown in place of the search box.
 */
const props = withDefaults(
    defineProps<{
        id?: string;
        /** Builds the lookup URL for a search term. */
        url: (term: string) => string;
        placeholder?: string;
        /** Returns a reason when an item cannot be picked. */
        disabledReason?: (item: T) => string | null;
        invalid?: boolean;
    }>(),
    {
        placeholder: 'Хайх...',
        disabledReason: () => null,
    },
);

const selected = defineModel<T | null>({ default: null });

defineSlots<{
    item(props: { item: T }): unknown;
    selected(props: { item: T }): unknown;
}>();

const term = ref('');
const results = ref<T[]>([]) as { value: T[] };
const open = ref(false);
const loading = ref(false);
const highlighted = ref(0);
const input = ref<InstanceType<typeof Input> | null>(null);
let requestId = 0;

async function load() {
    const current = ++requestId;
    loading.value = true;

    try {
        const response = await fetch(props.url(term.value.trim()), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (current === requestId && response.ok) {
            results.value = (await response.json()) as T[];
            highlighted.value = 0;
        }
    } finally {
        if (current === requestId) {
            loading.value = false;
        }
    }
}

const loadDebounced = useDebounceFn(load, 250);

watch(term, () => {
    open.value = true;
    void loadDebounced();
});

function onFocus() {
    open.value = true;

    if (results.value.length === 0) {
        void load();
    }
}

function choose(item: T) {
    if (props.disabledReason(item)) {
        return;
    }

    selected.value = item;
    open.value = false;
    term.value = '';
}

async function clear() {
    selected.value = null;
    results.value = [];
    await nextTick();
    (input.value?.$el as HTMLInputElement | undefined)?.focus();
}

function onKeydown(event: KeyboardEvent) {
    if (!open.value && ['ArrowDown', 'ArrowUp'].includes(event.key)) {
        open.value = true;

        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlighted.value = Math.min(
            highlighted.value + 1,
            results.value.length - 1,
        );
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const item = results.value[highlighted.value];

        if (item) {
            choose(item);
        }
    } else if (event.key === 'Escape') {
        open.value = false;
    }
}

function onBlur() {
    // Let a click on a result register before the list closes.
    window.setTimeout(() => (open.value = false), 150);
}
</script>

<template>
    <div v-if="selected" class="flex items-start gap-3 rounded-md border p-3">
        <div class="min-w-0 flex-1">
            <slot name="selected" :item="selected" />
        </div>
        <Button
            type="button"
            variant="ghost"
            size="icon-sm"
            aria-label="Солих"
            @click="clear"
        >
            <X />
        </Button>
    </div>

    <div v-else class="relative">
        <Search
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            :id="id"
            ref="input"
            v-model="term"
            type="search"
            autocomplete="off"
            class="pl-9"
            :placeholder="placeholder"
            :aria-invalid="invalid || undefined"
            role="combobox"
            :aria-expanded="open"
            @focus="onFocus"
            @blur="onBlur"
            @keydown="onKeydown"
        />
        <Spinner
            v-if="loading"
            class="absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
        />

        <ul
            v-if="open"
            role="listbox"
            class="absolute z-50 mt-1 max-h-80 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
        >
            <li
                v-if="!loading && results.length === 0"
                class="px-3 py-6 text-center text-sm text-muted-foreground"
            >
                Илэрц олдсонгүй
            </li>
            <li
                v-for="(item, index) in results"
                :key="item.id"
                role="option"
                :aria-selected="index === highlighted"
                :aria-disabled="!!disabledReason(item)"
                :class="
                    cn(
                        'cursor-pointer rounded-sm px-3 py-2 text-sm',
                        index === highlighted && 'bg-accent',
                        disabledReason(item) && 'cursor-not-allowed opacity-60',
                    )
                "
                @mousedown.prevent="choose(item)"
                @mouseenter="highlighted = index"
            >
                <slot name="item" :item="item" />
                <p
                    v-if="disabledReason(item)"
                    class="mt-0.5 text-xs text-destructive"
                >
                    {{ disabledReason(item) }}
                </p>
            </li>
        </ul>
    </div>
</template>
