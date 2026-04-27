<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Харагдах байдал',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Харагдах байдал" />

    <h1 class="sr-only">Харагдах байдал</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Харагдах байдал"
            description="Цайвар эсвэл бараан өнгө сонгох"
        />
        <AppearanceTabs />
    </div>
</template>
