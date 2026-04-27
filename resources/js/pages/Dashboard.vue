<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    BookCopy as BookCopyIcon,
    BookOpen,
    BookUp,
    CircleCheck,
    Plus,
    TriangleAlert,
    UserPlus,
    Users,
} from '@lucide/vue';
import BookController from '@/actions/App/Http/Controllers/BookController';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import DueBadge from '@/components/DueBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate, formatDateTime } from '@/lib/library';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { Loan } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Хянах самбар', href: dashboard() }],
    },
});

const props = defineProps<{
    stats: {
        books: number;
        copies: number;
        available: number;
        onLoan: number;
        overdue: number;
        members: number;
    };
    overdueLoans: Loan[];
    dueSoon: Loan[];
    recentLoans: Loan[];
}>();

const cards = [
    {
        label: 'Ном (гарчиг)',
        value: props.stats.books,
        icon: BookOpen,
        href: BookController.index(),
    },
    {
        label: 'Нийт хувь',
        value: props.stats.copies,
        icon: BookCopyIcon,
        href: BookController.index(),
    },
    {
        label: 'Тавиур дээр байгаа',
        value: props.stats.available,
        icon: CircleCheck,
        href: BookController.index({ query: { availability: 'available' } }),
    },
    {
        label: 'Зээлд байгаа',
        value: props.stats.onLoan,
        icon: ArrowLeftRight,
        href: LoanController.index(),
    },
    {
        label: 'Хугацаа хэтэрсэн',
        value: props.stats.overdue,
        icon: TriangleAlert,
        href: LoanController.index({ query: { status: 'overdue' } }),
        alert: props.stats.overdue > 0,
    },
    {
        label: 'Идэвхтэй уншигч',
        value: props.stats.members,
        icon: Users,
        href: MemberController.index(),
    },
];
</script>

<template>
    <Head title="Хянах самбар" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Хянах самбар"
            description="Номын сангийн өнөөдрийн байдал"
        >
            <Button as-child>
                <Link :href="LoanController.create()">
                    <BookUp /> Ном зээлүүлэх
                </Link>
            </Button>
            <Button variant="outline" as-child>
                <Link :href="BookController.create()"><Plus /> Ном нэмэх</Link>
            </Button>
            <Button variant="outline" as-child>
                <Link :href="MemberController.create()">
                    <UserPlus /> Уншигч нэмэх
                </Link>
            </Button>
        </PageHeader>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            <Link
                v-for="card in cards"
                :key="card.label"
                :href="card.href"
                :class="
                    cn(
                        'rounded-xl border bg-card p-4 transition-colors hover:bg-accent/50',
                        card.alert &&
                            'border-red-200 bg-red-50 hover:bg-red-100 dark:border-red-900 dark:bg-red-950/50',
                    )
                "
            >
                <div
                    :class="
                        cn(
                            'flex items-center gap-2 text-sm text-muted-foreground',
                            card.alert && 'text-red-700 dark:text-red-300',
                        )
                    "
                >
                    <component :is="card.icon" class="size-4" />
                    {{ card.label }}
                </div>
                <div
                    :class="
                        cn(
                            'mt-2 text-2xl font-semibold tabular-nums',
                            card.alert && 'text-red-700 dark:text-red-300',
                        )
                    "
                >
                    {{ card.value }}
                </div>
            </Link>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TriangleAlert class="size-4 text-red-600" />
                        Хугацаа хэтэрсэн
                    </CardTitle>
                    <CardDescription>
                        Буцаах хугацаа нь өнгөрсөн зээлүүд
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="overdueLoans.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        Хугацаа хэтэрсэн зээл алга.
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="loan in overdueLoans"
                            :key="loan.id"
                            class="flex items-center justify-between gap-3 py-2.5"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="
                                        BookController.show(loan.copy!.book!.id)
                                    "
                                    class="block truncate font-medium hover:underline"
                                >
                                    {{ loan.copy?.book?.title }}
                                </Link>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    <Link
                                        :href="
                                            MemberController.show(
                                                loan.member_id,
                                            )
                                        "
                                        class="hover:underline"
                                    >
                                        {{ loan.member?.name }}
                                    </Link>
                                    <template v-if="loan.member?.phone">
                                        · {{ loan.member.phone }}
                                    </template>
                                </p>
                            </div>
                            <DueBadge :loan="loan" />
                        </li>
                    </ul>
                    <div
                        v-if="stats.overdue > overdueLoans.length"
                        class="pt-3 text-right"
                    >
                        <Link
                            :href="
                                LoanController.index({
                                    query: { status: 'overdue' },
                                })
                            "
                            class="text-sm text-primary hover:underline"
                        >
                            Бүгдийг харах ({{ stats.overdue }})
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Ойрын 3 хоногт буцаах</CardTitle>
                    <CardDescription>
                        Удахгүй хугацаа нь дуусах зээлүүд
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="dueSoon.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        Ойрын өдрүүдэд буцаах ном алга.
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="loan in dueSoon"
                            :key="loan.id"
                            class="flex items-center justify-between gap-3 py-2.5"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {{ loan.copy?.book?.title }}
                                </p>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ loan.member?.name }} ·
                                    {{ formatDate(loan.due_at) }}
                                </p>
                            </div>
                            <DueBadge :loan="loan" />
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Сүүлийн зээлүүд</CardTitle>
            </CardHeader>
            <CardContent>
                <p
                    v-if="recentLoans.length === 0"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    Одоогоор зээл бүртгэгдээгүй байна.
                </p>
                <ul v-else class="divide-y">
                    <li
                        v-for="loan in recentLoans"
                        :key="loan.id"
                        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2.5"
                    >
                        <div class="min-w-0">
                            <span class="font-medium">{{
                                loan.copy?.book?.title
                            }}</span>
                            <span class="text-muted-foreground">
                                → {{ loan.member?.name }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-sm text-muted-foreground tabular-nums"
                            >
                                {{ formatDateTime(loan.borrowed_at) }}
                            </span>
                            <DueBadge :loan="loan" />
                        </div>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
