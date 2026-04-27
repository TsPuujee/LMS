<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import TableCell from "./TableCell.vue"
import TableRow from "./TableRow.vue"

const props = withDefaults(defineProps<{
  class?: HTMLAttributes["class"]
  colspan?: number
}>(), {
  colspan: 1,
})
</script>

<template>
  <TableRow class="hover:bg-transparent">
    <TableCell :class="cn('text-muted-foreground p-6 text-center text-sm', props.class)" :colspan="props.colspan">
      <slot />
    </TableCell>
  </TableRow>
</template>
