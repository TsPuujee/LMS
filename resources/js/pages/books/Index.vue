<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import BookController from '@/actions/App/Http/Controllers/BookController';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useListFilters } from '@/composables/useListFilters';
import { cn } from '@/lib/utils';
import type { Book, Category, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Ном', href: BookController.index() }],
    },
});

const props = defineProps<{
    books: Paginated<Book>;
    categories: Pick<Category, 'id' | 'name'>[];
    filters: {
        search: string;
        category: number | null;
        availability: string;
    };
}>();

const { filters, reset, isFiltered } = useListFilters(
    {
        search: props.filters.search,
        category: props.filters.category
            ? String(props.filters.category)
            : 'all',
        availability: props.filters.availability || 'all',
    },
    { category: 'all', availability: 'all' },
);
</script>

<template>
    <Head title="Ном" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Ном"
            description="Номын сангийн бүх ном, хувиудын тоо"
        >
            <Button as-child>
                <Link :href="BookController.create()"><Plus /> Ном нэмэх</Link>
            </Button>
        </PageHeader>

        <div class="flex flex-col gap-2 md:flex-row md:items-center">
            <div class="relative md:max-w-sm md:flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    type="search"
                    class="pl-9"
                    placeholder="Нэр, зохиогч, ISBN, бүртгэлийн №"
                />
            </div>
            <Select v-model="filters.category">
                <SelectTrigger class="w-full md:w-56">
                    <SelectValue placeholder="Ангилал" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Бүх ангилал</SelectItem>
                    <SelectItem
                        v-for="category in categories"
                        :key="category.id"
                        :value="String(category.id)"
                    >
                        {{ category.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="filters.availability">
                <SelectTrigger class="w-full md:w-48">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Бүгд</SelectItem>
                    <SelectItem value="available"
                        >Тавиур дээр байгаа</SelectItem
                    >
                    <SelectItem value="unavailable"
                        >Бүгд зээлд/байхгүй</SelectItem
                    >
                </SelectContent>
            </Select>
            <Button v-if="isFiltered()" variant="ghost" @click="reset">
                Цэвэрлэх
            </Button>
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="pl-4">Ном</TableHead>
                        <TableHead class="hidden md:table-cell"
                            >Ангилал</TableHead
                        >
                        <TableHead class="hidden lg:table-cell">Он</TableHead>
                        <TableHead class="hidden lg:table-cell">ISBN</TableHead>
                        <TableHead class="pr-4 text-right">
                            Байгаа / Нийт
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="books.data.length === 0" :colspan="5">
                        Ном олдсонгүй.
                    </TableEmpty>
                    <TableRow v-for="book in books.data" :key="book.id">
                        <TableCell class="pl-4">
                            <Link
                                :href="BookController.show(book.id)"
                                class="font-medium hover:underline"
                            >
                                {{ book.title }}
                            </Link>
                            <p class="text-sm text-muted-foreground">
                                {{ book.author }}
                            </p>
                        </TableCell>
                        <TableCell class="hidden md:table-cell">
                            {{ book.category?.name }}
                        </TableCell>
                        <TableCell
                            class="hidden text-muted-foreground tabular-nums lg:table-cell"
                        >
                            {{ book.published_year ?? '—' }}
                        </TableCell>
                        <TableCell
                            class="hidden font-mono text-xs text-muted-foreground lg:table-cell"
                        >
                            {{ book.isbn ?? '—' }}
                        </TableCell>
                        <TableCell class="pr-4 text-right">
                            <span
                                :class="
                                    cn(
                                        'inline-flex min-w-14 justify-center rounded-full border px-2 py-0.5 text-xs font-medium tabular-nums',
                                        (book.available_copies_count ?? 0) > 0
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'border-neutral-200 bg-neutral-100 text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400',
                                    )
                                "
                            >
                                {{ book.available_copies_count }} /
                                {{ book.total_copies_count }}
                            </span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="books" />
    </div>
</template>
