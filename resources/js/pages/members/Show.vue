<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BookUp,
    CalendarPlus,
    IdCard,
    Mail,
    MapPin,
    Pencil,
    Phone,
    Trash2,
    Undo2,
} from '@lucide/vue';
import { computed } from 'vue';
import BookController from '@/actions/App/Http/Controllers/BookController';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import ConfirmAction from '@/components/ConfirmAction.vue';
import DueBadge from '@/components/DueBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import ReturnLoanDialog from '@/components/ReturnLoanDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/library';
import type { Loan, Member } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Уншигчид', href: MemberController.index() }],
    },
});

const props = defineProps<{
    member: Member;
    openLoans: Loan[];
    history: Loan[];
    maxActiveLoans: number;
}>();

const atLimit = computed(
    () =>
        props.maxActiveLoans > 0 &&
        props.openLoans.length >= props.maxActiveLoans,
);

const canBorrow = computed(() => props.member.is_active && !atLimit.value);

function withMember(loan: Loan): Loan {
    return { ...loan, member: props.member };
}

function renew(loan: Loan) {
    router.post(
        LoanController.renew.url(loan.id),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="member.name" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader :title="member.name">
            <template #meta>
                <div
                    class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                >
                    <span class="font-mono">{{ member.member_no }}</span>
                    <Badge v-if="member.is_active" variant="secondary">
                        Идэвхтэй
                    </Badge>
                    <Badge v-else variant="destructive">Идэвхгүй</Badge>
                </div>
            </template>

            <Button :disabled="!canBorrow" :as-child="canBorrow">
                <Link
                    v-if="canBorrow"
                    :href="
                        LoanController.create({ query: { member: member.id } })
                    "
                >
                    <BookUp /> Ном зээлүүлэх
                </Link>
                <template v-else><BookUp /> Ном зээлүүлэх</template>
            </Button>
            <Button variant="outline" as-child>
                <Link :href="MemberController.edit(member.id)">
                    <Pencil /> Засах
                </Link>
            </Button>
            <ConfirmAction
                :action="MemberController.destroy(member.id)"
                title="Уншигч устгах уу?"
                :description="`${member.name} уншигчийг устгана. Зээлийн түүхтэй уншигчийг устгах боломжгүй, оронд нь идэвхгүй болгоно уу.`"
            >
                <Button variant="outline" aria-label="Устгах">
                    <Trash2 />
                </Button>
            </ConfirmAction>
        </PageHeader>

        <p
            v-if="atLimit"
            class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200"
        >
            Нэг уншигч зэрэг {{ maxActiveLoans }} хүртэл ном зээлнэ. Шинээр
            зээлэхийн өмнө нэгийг нь буцаана уу.
        </p>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle>Холбоо барих</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <p class="flex items-center gap-2">
                        <IdCard class="size-4 text-muted-foreground" />
                        <span class="font-mono">{{ member.member_no }}</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <Phone class="size-4 text-muted-foreground" />
                        {{ member.phone ?? '—' }}
                    </p>
                    <p class="flex items-center gap-2">
                        <Mail class="size-4 text-muted-foreground" />
                        {{ member.email ?? '—' }}
                    </p>
                    <p class="flex items-center gap-2">
                        <MapPin class="size-4 text-muted-foreground" />
                        {{ member.address ?? '—' }}
                    </p>
                    <p
                        v-if="member.note"
                        class="border-t pt-3 whitespace-pre-line text-muted-foreground"
                    >
                        {{ member.note }}
                    </p>
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Одоо зээлж байгаа ном</CardTitle>
                    <CardDescription>
                        {{ openLoans.length }} / {{ maxActiveLoans }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="px-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="pl-6">Ном</TableHead>
                                <TableHead>Авсан</TableHead>
                                <TableHead>Буцаах</TableHead>
                                <TableHead class="pr-6" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty
                                v-if="openLoans.length === 0"
                                :colspan="4"
                            >
                                Одоогоор ном зээлээгүй байна.
                            </TableEmpty>
                            <TableRow v-for="loan in openLoans" :key="loan.id">
                                <TableCell class="pl-6">
                                    <Link
                                        :href="
                                            BookController.show(
                                                loan.copy!.book!.id,
                                            )
                                        "
                                        class="font-medium hover:underline"
                                    >
                                        {{ loan.copy?.book?.title }}
                                    </Link>
                                    <p
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ loan.copy?.inventory_no }}
                                    </p>
                                </TableCell>
                                <TableCell class="tabular-nums">
                                    {{ formatDate(loan.borrowed_at) }}
                                </TableCell>
                                <TableCell>
                                    <div
                                        class="flex flex-col items-start gap-1"
                                    >
                                        <span class="tabular-nums">
                                            {{ formatDate(loan.due_at) }}
                                        </span>
                                        <DueBadge :loan="loan" />
                                    </div>
                                </TableCell>
                                <TableCell class="pr-6">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="renew(loan)"
                                        >
                                            <CalendarPlus /> Сунгах
                                        </Button>
                                        <ReturnLoanDialog
                                            :loan="withMember(loan)"
                                        >
                                            <Button size="sm">
                                                <Undo2 /> Буцаах
                                            </Button>
                                        </ReturnLoanDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Өмнө зээлсэн ном</CardTitle>
            </CardHeader>
            <CardContent class="px-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="pl-6">Ном</TableHead>
                            <TableHead>Хувь</TableHead>
                            <TableHead>Авсан</TableHead>
                            <TableHead>Буцаах ёстой</TableHead>
                            <TableHead class="pr-6">Буцаасан</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="history.length === 0" :colspan="5">
                            Түүх хоосон байна.
                        </TableEmpty>
                        <TableRow v-for="loan in history" :key="loan.id">
                            <TableCell class="pl-6">
                                {{ loan.copy?.book?.title }}
                            </TableCell>
                            <TableCell class="font-mono text-xs">
                                {{ loan.copy?.inventory_no }}
                            </TableCell>
                            <TableCell class="tabular-nums">
                                {{ formatDate(loan.borrowed_at) }}
                            </TableCell>
                            <TableCell class="tabular-nums">
                                {{ formatDate(loan.due_at) }}
                            </TableCell>
                            <TableCell class="pr-6 tabular-nums">
                                {{ formatDate(loan.returned_at) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
