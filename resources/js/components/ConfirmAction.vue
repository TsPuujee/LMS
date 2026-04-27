<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

/**
 * Wraps a trigger (usually a delete button) in a confirmation dialog and
 * sends the request when the librarian confirms.
 */
const props = withDefaults(
    defineProps<{
        action: {
            url: string;
            method: 'get' | 'post' | 'put' | 'patch' | 'delete';
        };
        title: string;
        description?: string;
        confirmLabel?: string;
        destructive?: boolean;
    }>(),
    {
        confirmLabel: 'Устгах',
        destructive: true,
    },
);

const open = ref(false);
const processing = ref(false);

function confirm() {
    router.visit(props.action.url, {
        method: props.action.method,
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => {
            processing.value = false;
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Болих</Button>
                </DialogClose>
                <Button
                    :variant="destructive ? 'destructive' : 'default'"
                    :disabled="processing"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
