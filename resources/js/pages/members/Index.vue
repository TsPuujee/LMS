<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search, UserPlus } from '@lucide/vue';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
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
import type { Member, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Уншигчид', href: MemberController.index() }],
    },
});

const props = defineProps<{
    members: Paginated<Member>;
    filters: { search: string; status: string };
}>();

const { filters, reset, isFiltered } = useListFilters(
    {
        search: props.filters.search,
        status: props.filters.status || 'all',
    },
    { status: 'all' },
);
</script>

<template>
    <Head title="Уншигчид" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Уншигчид"
            description="Номын сангийн бүртгэлтэй уншигчид"
        >
            <Button as-child>
                <Link :href="MemberController.create()">
                    <UserPlus /> Уншигч нэмэх
                </Link>
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
                    placeholder="Нэр, үнэмлэхний №, утас, и-мэйл"
                />
            </div>
            <Select v-model="filters.status">
                <SelectTrigger class="w-full md:w-56">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Бүх уншигч</SelectItem>
                    <SelectItem value="borrowing">Ном зээлж байгаа</SelectItem>
                    <SelectItem value="overdue">Хугацаа хэтрүүлсэн</SelectItem>
                    <SelectItem value="active">Идэвхтэй</SelectItem>
                    <SelectItem value="inactive">Идэвхгүй</SelectItem>
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
                        <TableHead class="pl-4">Уншигч</TableHead>
                        <TableHead class="hidden md:table-cell">Утас</TableHead>
                        <TableHead class="hidden lg:table-cell"
                            >И-мэйл</TableHead
                        >
                        <TableHead class="text-right">Зээлж буй</TableHead>
                        <TableHead class="pr-4 text-right">Төлөв</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="members.data.length === 0" :colspan="5">
                        Уншигч олдсонгүй.
                    </TableEmpty>
                    <TableRow v-for="member in members.data" :key="member.id">
                        <TableCell class="pl-4">
                            <Link
                                :href="MemberController.show(member.id)"
                                class="font-medium hover:underline"
                            >
                                {{ member.name }}
                            </Link>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ member.member_no }}
                            </p>
                        </TableCell>
                        <TableCell class="hidden tabular-nums md:table-cell">
                            {{ member.phone ?? '—' }}
                        </TableCell>
                        <TableCell
                            class="hidden text-muted-foreground lg:table-cell"
                        >
                            {{ member.email ?? '—' }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ member.open_loans_count }}
                            <span
                                v-if="member.overdue_loans_count"
                                class="ml-1 text-xs font-medium text-red-600 dark:text-red-400"
                            >
                                ({{ member.overdue_loans_count }} хэтэрсэн)
                            </span>
                        </TableCell>
                        <TableCell class="pr-4 text-right">
                            <Badge v-if="member.is_active" variant="secondary">
                                Идэвхтэй
                            </Badge>
                            <Badge v-else variant="outline">Идэвхгүй</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination :paginator="members" />
    </div>
</template>
