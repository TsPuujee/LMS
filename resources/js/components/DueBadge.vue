<script setup lang="ts">
import { computed } from 'vue';
import { dueLabel, dueState } from '@/lib/library';
import { cn } from '@/lib/utils';
import type { Loan } from '@/types';

const props = defineProps<{
    loan: Loan;
}>();

const classes = {
    returned:
        'border-neutral-200 bg-neutral-100 text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400',
    overdue:
        'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    today: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    soon: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    ok: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300',
};

const state = computed(() => dueState(props.loan));
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                classes[state],
            )
        "
    >
        {{ dueLabel(loan) }}
    </span>
</template>
