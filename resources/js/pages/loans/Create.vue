<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import LookupController from '@/actions/App/Http/Controllers/LookupController';
import CopyStatusBadge from '@/components/CopyStatusBadge.vue';
import FormField from '@/components/FormField.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchSelect from '@/components/SearchSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { copyStatusLabels, todayIso } from '@/lib/library';
import type { BookCopy, MemberSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Ном зээлүүлэх', href: LoanController.create() },
        ],
    },
});

const props = defineProps<{
    member: MemberSummary | null;
    copy: BookCopy | null;
    defaultDueAt: string;
    loanDays: number;
    maxActiveLoans: number;
}>();

const member = ref<MemberSummary | null>(props.member);
const copy = ref<BookCopy | null>(props.copy);

const form = useForm({
    member_id: props.member?.id ?? null,
    book_copy_id: props.copy?.id ?? null,
    due_at: props.defaultDueAt,
    note: '',
    another: false,
});

const memberUrl = (q: string) => LookupController.members.url({ query: { q } });
const copyUrl = (q: string) => LookupController.copies.url({ query: { q } });

watch(member, (value) => (form.member_id = value?.id ?? null));
watch(copy, (value) => (form.book_copy_id = value?.id ?? null));

function memberProblem(m: MemberSummary): string | null {
    if (m.is_active === false) {
        return 'Уншигчийн эрх идэвхгүй байна.';
    }

    if (
        props.maxActiveLoans > 0 &&
        (m.open_loans_count ?? 0) >= props.maxActiveLoans
    ) {
        return `Аль хэдийн ${props.maxActiveLoans} ном зээлсэн байна.`;
    }

    return null;
}

function copyProblem(c: BookCopy): string | null {
    return c.status === 'available'
        ? null
        : `Энэ хувь одоогоор «${copyStatusLabels[c.status]}» төлөвтэй.`;
}

const blocking = computed(
    () =>
        (member.value && memberProblem(member.value)) ||
        (copy.value && copyProblem(copy.value)) ||
        null,
);

const canSubmit = computed(
    () => !!member.value && !!copy.value && !blocking.value,
);

function submit(another: boolean) {
    form.another = another;
    form.submit(LoanController.store(), {
        onSuccess: () => {
            if (another) {
                // Same member, next book.
                copy.value = null;
                form.note = '';
            }
        },
    });
}
</script>

<template>
    <Head title="Ном зээлүүлэх" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Ном зээлүүлэх"
            :description="`Уншигч болон номын хувийг сонгоно уу. Анхдагч хугацаа ${loanDays} хоног.`"
        />

        <form class="space-y-6" @submit.prevent="submit(false)">
            <Card>
                <CardContent class="space-y-6">
                    <FormField
                        label="1. Уншигч"
                        for="member"
                        :error="form.errors.member_id"
                    >
                        <SearchSelect
                            id="member"
                            v-model="member"
                            :url="memberUrl"
                            placeholder="Нэр, үнэмлэхний №, утасны дугаараар хайх"
                            :disabled-reason="memberProblem"
                            :invalid="!!form.errors.member_id"
                        >
                            <template #item="{ item }">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span class="font-medium">{{
                                        item.name
                                    }}</span>
                                    <span
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ item.member_no }}
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ item.phone ?? 'Утасгүй' }} ·
                                    {{ item.open_loans_count ?? 0 }} ном зээлсэн
                                </p>
                            </template>
                            <template #selected="{ item }">
                                <p class="font-medium">{{ item.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    <span class="font-mono">{{
                                        item.member_no
                                    }}</span>
                                    <template v-if="item.phone">
                                        · {{ item.phone }}</template
                                    >
                                    · одоо {{ item.open_loans_count ?? 0 }} /
                                    {{ maxActiveLoans }} ном
                                </p>
                                <p
                                    v-if="memberProblem(item)"
                                    class="mt-1 text-sm text-destructive"
                                >
                                    {{ memberProblem(item) }}
                                </p>
                            </template>
                        </SearchSelect>
                    </FormField>

                    <FormField
                        label="2. Номын хувь"
                        for="copy"
                        :error="form.errors.book_copy_id"
                    >
                        <SearchSelect
                            id="copy"
                            v-model="copy"
                            :url="copyUrl"
                            placeholder="Номын нэр, зохиогч эсвэл бүртгэлийн №"
                            :disabled-reason="copyProblem"
                            :invalid="!!form.errors.book_copy_id"
                        >
                            <template #item="{ item }">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span class="font-medium">{{
                                        item.book?.title
                                    }}</span>
                                    <CopyStatusBadge :status="item.status" />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    <span class="font-mono">{{
                                        item.inventory_no
                                    }}</span>
                                    · {{ item.book?.author }}
                                    <template v-if="item.shelf">
                                        · Тавиур {{ item.shelf }}</template
                                    >
                                </p>
                            </template>
                            <template #selected="{ item }">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium">
                                        {{ item.book?.title }}
                                    </p>
                                    <CopyStatusBadge :status="item.status" />
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    <span class="font-mono">{{
                                        item.inventory_no
                                    }}</span>
                                    · {{ item.book?.author }}
                                </p>
                            </template>
                        </SearchSelect>
                    </FormField>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <FormField
                            label="3. Буцаах огноо"
                            for="due_at"
                            :error="form.errors.due_at"
                        >
                            <Input
                                id="due_at"
                                v-model="form.due_at"
                                type="date"
                                :min="todayIso()"
                                required
                            />
                        </FormField>
                        <FormField
                            label="Тэмдэглэл"
                            for="note"
                            :error="form.errors.note"
                        >
                            <Input
                                id="note"
                                v-model="form.note"
                                placeholder="Заавал биш"
                            />
                        </FormField>
                    </div>

                    <InputError
                        :message="
                            (form.errors as Record<string, string | undefined>)
                                .loan
                        "
                    />
                </CardContent>
            </Card>

            <div class="flex flex-wrap gap-2">
                <Button type="submit" :disabled="!canSubmit || form.processing">
                    Зээлүүлэх
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="!canSubmit || form.processing"
                    @click="submit(true)"
                >
                    Зээлүүлээд дахин ном нэмэх
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="LoanController.index()">Болих</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
