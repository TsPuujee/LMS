<script setup lang="ts">
import { copyStatusClasses, copyStatusLabels } from '@/lib/library';
import { cn } from '@/lib/utils';
import type { CopyStatus } from '@/types';

defineProps<{
    status: CopyStatus;
}>();
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                copyStatusClasses[status],
            )
        "
    >
        {{ copyStatusLabels[status] }}
    </span>
</template>
