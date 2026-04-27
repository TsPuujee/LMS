<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BookUp, CalendarPlus, Search, Undo2 } from '@lucide/vue';
import BookController from '@/actions/App/Http/Controllers/BookController';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import DueBadge from '@/components/DueBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import ReturnLoanDialog from '@/components/ReturnLoanDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import { formatDate } from '@/lib/library';
import { cn } from '@/lib/utils';
import type { Loan, Option, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Хэнд ямар ном байна', href: LoanController.index() },
        ],
    },
});

const props = defineProps<{
    loans: Paginated<Loan>;
    filters: { search: string; status: string };
    counts: { open: number; overdue: number };
    renewDays: number;
    returnStatusOptions: Option[];
}>();

const { filters } = useListFilters({
    search: props.filters.search,
    status: props.filters.status || 'all',
});

const tabs = [
    { value: 'all', label: 'Бүгд', count: () => props.counts.open },
    {
        value: 'overdue',
        label: 'Хугацаа хэтэрсэн',
        count: () => props.counts.overdue,
    },
    { value: 'due_soon', label: 'Ойрын 3 хоногт', count: () => null },
];

function renew(loan: Loan) {
    router.post(
        LoanController.renew.url(loan.id),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Хэнд ямар ном байна" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Хэнд ямар ном байна"
            description="Одоо зээлд байгаа бүх ном, хэнд байгаа, хэзээ буцаах"
        >
            <Button as-child>
                <Link :href="LoanController.create()">
                    <BookUp /> Ном зээлүүлэх
                </Link>
            </Button>
        </PageHeader>

        <div class="flex flex-col gap-2 md:flex-row md:items-center">
            <div class="inline-flex rounded-lg bg-muted p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    :class="
                        cn(
                            'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                            filters.status === tab.value
                                ? 'bg-background shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="filters.status = tab.value"
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.count() !== null"
                        :class="
                            cn(
                                'ml-1 tabular-nums',
                                tab.value === 'overdue' &&
                                    (tab.count() ?? 0) > 0 &&
                                    'text-red-600 dark:text-red-400',
                            )
                        "
                    >
                        {{ tab.count() }}
                    </span>
                </button>
            </div>
            <div class="relative md:ml-auto md:w-80">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    type="search"
                    class="pl-9"
                    placeholder="Ном, бүртгэлийн №, уншигч, утас"
                />
            </div>
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="pl-4">Ном</TableHead>
                        <TableHead>Хэнд байгаа</TableHead>
                        <TableHead class="hidden md:table-cell"
                            >Авсан</TableHead
                        >
                        <TableHead>Буцаах</TableHead>
                        <TableHead class="pr-4" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="loans.data.length === 0" :colspan="5">
                        {{
                            filters.search || filters.status !== 'all'
                                ? 'Шүүлтэд тохирох зээл олдсонгүй.'
                                : 'Одоогоор зээлд байгаа ном алга.'
                        }}
                    </TableEmpty>
                    <TableRow
                        v-for="loan in loans.data"
                        :key="loan.id"
                        :class="
                            loan.is_overdue &&
                            'bg-red-50/50 hover:bg-red-50 dark:bg-red-950/20 dark:hover:bg-red-950/40'
                        "
                    >
                        <TableCell class="pl-4">
                            <Link
                                :href="BookController.show(loan.copy!.book!.id)"
                                class="font-medium hover:underline"
                            >
                                {{ loan.copy?.book?.title }}
                            </Link>
                            <p class="text-xs text-muted-foreground">
                                <span class="font-mono">{{
                                    loan.copy?.inventory_no
                                }}</span>
                                · {{ loan.copy?.book?.author }}
                            </p>
                        </TableCell>
                        <TableCell>
                            <Link
                                :href="MemberController.show(loan.member_id)"
                                class="font-medium hover:underline"
                            >
                                {{ loan.member?.name }}
                            </Link>
                            <p class="text-xs text-muted-foreground">
                                {{ loan.member?.member_no }}
                                <template v-if="loan.member?.phone">
                                    · {{ loan.member.phone }}
                                </template>
                            </p>
                        </TableCell>
                        <TableCell
                            class="hidden text-muted-foreground tabular-nums md:table-cell"
                        >
                            {{ formatDate(loan.borrowed_at) }}
                        </TableCell>
                        <TableCell>
                            <div class="flex flex-col items-start gap-1">
                                <span class="tabular-nums">
                                    {{ formatDate(loan.due_at) }}
                                </span>
                                <DueBadge :loan="loan" />
                            </div>
                        </TableCell>
                        <TableCell class="pr-4">
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :title="`${renewDays} хоногоор сунгах`"
                                    @click="renew(loan)"
                                >
                                    <CalendarPlus />
                                    <span class="hidden lg:inline">Сунгах</span>
                                </Button>
                                <ReturnLoanDialog :loan="loan">
                                    <Button size="sm">
                                        <Undo2 />
                                        <span class="hidden lg:inline">
                                            Буцаах
                                        </span>
                                    </Button>
                                </ReturnLoanDialog>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="loans" />
    </div>
</template>
