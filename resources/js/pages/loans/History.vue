<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import BookController from '@/actions/App/Http/Controllers/BookController';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import DueBadge from '@/components/DueBadge.vue';
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
import { formatDate, formatDateTime } from '@/lib/library';
import type { Loan, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Зээлийн түүх', href: LoanController.history() },
        ],
    },
});

const props = defineProps<{
    loans: Paginated<Loan>;
    filters: {
        search: string;
        status: string;
        from: string | null;
        to: string | null;
    };
}>();

const { filters, reset, isFiltered } = useListFilters(
    {
        search: props.filters.search,
        status: props.filters.status || 'all',
        from: props.filters.from ?? '',
        to: props.filters.to ?? '',
    },
    { status: 'all' },
);
</script>

<template>
    <Head title="Зээлийн түүх" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Зээлийн түүх"
            description="Бүх зээл — буцаасан болон одоо зээлд байгаа"
        />

        <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
            <div class="relative lg:max-w-xs lg:flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    type="search"
                    class="pl-9"
                    placeholder="Ном, бүртгэлийн №, уншигч"
                />
            </div>
            <Select v-model="filters.status">
                <SelectTrigger class="w-full lg:w-48">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Бүх зээл</SelectItem>
                    <SelectItem value="open">Зээлд байгаа</SelectItem>
                    <SelectItem value="overdue">Хугацаа хэтэрсэн</SelectItem>
                    <SelectItem value="returned">Буцаасан</SelectItem>
                </SelectContent>
            </Select>
            <div class="flex items-center gap-2">
                <Input
                    v-model="filters.from"
                    type="date"
                    class="w-full lg:w-40"
                    aria-label="Эхлэх огноо"
                />
                <span class="text-muted-foreground">–</span>
                <Input
                    v-model="filters.to"
                    type="date"
                    class="w-full lg:w-40"
                    aria-label="Дуусах огноо"
                />
            </div>
            <Button v-if="isFiltered()" variant="ghost" @click="reset">
                Цэвэрлэх
            </Button>
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="pl-4">Ном</TableHead>
                        <TableHead>Уншигч</TableHead>
                        <TableHead>Авсан</TableHead>
                        <TableHead class="hidden md:table-cell">
                            Буцаах ёстой
                        </TableHead>
                        <TableHead>Буцаасан</TableHead>
                        <TableHead class="hidden xl:table-cell">
                            Олгосон
                        </TableHead>
                        <TableHead class="pr-4 text-right">Төлөв</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="loans.data.length === 0" :colspan="7">
                        Зээл олдсонгүй.
                    </TableEmpty>
                    <TableRow v-for="loan in loans.data" :key="loan.id">
                        <TableCell class="pl-4">
                            <Link
                                :href="BookController.show(loan.copy!.book!.id)"
                                class="font-medium hover:underline"
                            >
                                {{ loan.copy?.book?.title }}
                            </Link>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ loan.copy?.inventory_no }}
                            </p>
                        </TableCell>
                        <TableCell>
                            <Link
                                :href="MemberController.show(loan.member_id)"
                                class="hover:underline"
                            >
                                {{ loan.member?.name }}
                            </Link>
                        </TableCell>
                        <TableCell class="tabular-nums">
                            {{ formatDateTime(loan.borrowed_at) }}
                        </TableCell>
                        <TableCell class="hidden tabular-nums md:table-cell">
                            {{ formatDate(loan.due_at) }}
                            <span
                                v-if="loan.renewals > 0"
                                class="text-xs text-muted-foreground"
                            >
                                ({{ loan.renewals }} удаа сунгасан)
                            </span>
                        </TableCell>
                        <TableCell class="tabular-nums">
                            {{ formatDateTime(loan.returned_at) }}
                        </TableCell>
                        <TableCell
                            class="hidden text-muted-foreground xl:table-cell"
                        >
                            {{ loan.issuer?.name ?? '—' }}
                        </TableCell>
                        <TableCell class="pr-4 text-right">
                            <DueBadge :loan="loan" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="loans" />
    </div>
</template>
