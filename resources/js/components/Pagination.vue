<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

defineProps<{
    paginator: Paginated<unknown>;
}>();
</script>

<template>
    <div
        v-if="paginator.total > 0"
        class="flex flex-col items-center justify-between gap-3 text-sm sm:flex-row"
    >
        <p class="text-muted-foreground">
            Нийт {{ paginator.total }}-с {{ paginator.from }}–{{ paginator.to }}
        </p>
        <nav
            v-if="paginator.last_page > 1"
            class="flex flex-wrap items-center gap-1"
            aria-label="Хуудаслалт"
        >
            <template v-for="(link, i) in paginator.links" :key="i">
                <span
                    v-if="!link.url"
                    class="rounded-md px-3 py-1.5 text-muted-foreground/60"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    :class="
                        cn(
                            'rounded-md border px-3 py-1.5 transition-colors hover:bg-accent',
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground hover:bg-primary/90'
                                : 'border-transparent',
                        )
                    "
                    :aria-current="link.active ? 'page' : undefined"
                >
                    <span v-html="link.label" />
                </Link>
            </template>
        </nav>
    </div>
</template>
