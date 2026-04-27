<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BookUp, Pencil, Plus, Trash2, Undo2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import BookController from '@/actions/App/Http/Controllers/BookController';
import BookCopyController from '@/actions/App/Http/Controllers/BookCopyController';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import ConfirmAction from '@/components/ConfirmAction.vue';
import CopyStatusBadge from '@/components/CopyStatusBadge.vue';
import DueBadge from '@/components/DueBadge.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import ReturnLoanDialog from '@/components/ReturnLoanDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { formatDate } from '@/lib/library';
import type { Book, BookCopy, CopyStatus, Loan, Option } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Ном', href: BookController.index() }],
    },
});

const props = defineProps<{
    book: Book;
    copies: BookCopy[];
    loans: Loan[];
    statusOptions: Option[];
}>();

const availableCopy = computed(() =>
    props.copies.find((copy) => copy.status === 'available'),
);
const availableCount = computed(
    () => props.copies.filter((copy) => copy.status === 'available').length,
);

const details = computed(() => [
    { label: 'ISBN', value: props.book.isbn },
    { label: 'Хэвлэлийн газар', value: props.book.publisher },
    { label: 'Хэвлэгдсэн он', value: props.book.published_year },
    { label: 'Хэл', value: props.book.language },
    { label: 'Хуудас', value: props.book.pages },
]);

/** The open loan of a copy in the shape the return dialog expects. */
function loanOf(copy: BookCopy): Loan | null {
    if (!copy.open_loan) {
        return null;
    }

    return {
        ...copy.open_loan,
        copy: {
            id: copy.id,
            inventory_no: copy.inventory_no,
            book: props.book,
        },
    };
}

// ---- add copies --------------------------------------------------------
const addOpen = ref(false);
const addForm = useForm({ quantity: 1, inventory_no: '', shelf: '', note: '' });

function openAdd() {
    addForm.reset();
    addForm.clearErrors();
    addOpen.value = true;
}

function submitAdd() {
    addForm.submit(BookCopyController.store(props.book.id), {
        preserveScroll: true,
        onSuccess: () => (addOpen.value = false),
    });
}

// ---- edit a copy ------------------------------------------------------
const editing = ref<BookCopy | null>(null);
const editOpen = ref(false);
const editForm = useForm<{
    inventory_no: string;
    shelf: string;
    status: CopyStatus;
    note: string;
}>({ inventory_no: '', shelf: '', status: 'available', note: '' });

const editableStatuses = computed(() =>
    editing.value?.status === 'borrowed'
        ? props.statusOptions.filter((o) => o.value === 'borrowed')
        : props.statusOptions.filter((o) => o.value !== 'borrowed'),
);

function openEdit(copy: BookCopy) {
    editing.value = copy;
    editForm.clearErrors();
    editForm.defaults({
        inventory_no: copy.inventory_no,
        shelf: copy.shelf ?? '',
        status: copy.status,
        note: copy.note ?? '',
    });
    editForm.reset();
    editOpen.value = true;
}

function submitEdit() {
    if (!editing.value) {
        return;
    }

    editForm.submit(BookCopyController.update(editing.value.id), {
        preserveScroll: true,
        onSuccess: () => (editOpen.value = false),
    });
}
</script>

<template>
    <Head :title="book.title" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader :title="book.title">
            <template #meta>
                <div
                    class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                >
                    <span>{{ book.author }}</span>
                    <Badge v-if="book.category" variant="secondary">
                        {{ book.category.name }}
                    </Badge>
                </div>
            </template>

            <Button v-if="availableCopy" as-child>
                <Link
                    :href="
                        LoanController.create({
                            query: { copy: availableCopy.id },
                        })
                    "
                >
                    <BookUp /> Зээлүүлэх
                </Link>
            </Button>
            <Button variant="outline" as-child>
                <Link :href="BookController.edit(book.id)">
                    <Pencil /> Засах
                </Link>
            </Button>
            <ConfirmAction
                :action="BookController.destroy(book.id)"
                title="Ном устгах уу?"
                :description="`«${book.title}» болон түүний бүх хувийг устгана. Зээлийн түүхтэй номыг устгах боломжгүй.`"
            >
                <Button variant="outline" aria-label="Устгах">
                    <Trash2 />
                </Button>
            </ConfirmAction>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-1">
                <CardHeader>
                    <CardTitle>Мэдээлэл</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <template v-for="item in details" :key="item.label">
                            <dt class="text-muted-foreground">
                                {{ item.label }}
                            </dt>
                            <dd class="font-medium break-words">
                                {{ item.value ?? '—' }}
                            </dd>
                        </template>
                        <dt class="text-muted-foreground">Хувь</dt>
                        <dd class="font-medium">
                            {{ availableCount }} байгаа / {{ copies.length }}
                            нийт
                        </dd>
                    </dl>
                    <p
                        v-if="book.description"
                        class="border-t pt-4 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ book.description }}
                    </p>
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Хувиуд ба одоо хэнд байгаа</CardTitle>
                    <CardAction>
                        <Button size="sm" variant="outline" @click="openAdd">
                            <Plus /> Хувь нэмэх
                        </Button>
                    </CardAction>
                </CardHeader>
                <CardContent class="px-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="pl-6">Бүртгэлийн №</TableHead>
                                <TableHead>Төлөв</TableHead>
                                <TableHead>Хэнд байгаа</TableHead>
                                <TableHead class="hidden sm:table-cell">
                                    Тавиур
                                </TableHead>
                                <TableHead class="w-32" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty v-if="copies.length === 0" :colspan="5">
                                Хувь бүртгэгдээгүй байна. «Хувь нэмэх» товчоор
                                нэмнэ үү.
                            </TableEmpty>
                            <TableRow v-for="copy in copies" :key="copy.id">
                                <TableCell class="pl-6 font-mono text-xs">
                                    {{ copy.inventory_no }}
                                    <p
                                        v-if="copy.note"
                                        class="font-sans text-muted-foreground"
                                    >
                                        {{ copy.note }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <CopyStatusBadge :status="copy.status" />
                                </TableCell>
                                <TableCell>
                                    <div
                                        v-if="copy.open_loan"
                                        class="flex flex-col items-start gap-1"
                                    >
                                        <Link
                                            :href="
                                                MemberController.show(
                                                    copy.open_loan.member.id,
                                                )
                                            "
                                            class="font-medium hover:underline"
                                        >
                                            {{ copy.open_loan.member.name }}
                                        </Link>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                formatDate(
                                                    copy.open_loan.due_at,
                                                )
                                            }}
                                            хүртэл
                                        </span>
                                        <DueBadge :loan="copy.open_loan" />
                                    </div>
                                    <span v-else class="text-muted-foreground">
                                        —
                                    </span>
                                </TableCell>
                                <TableCell
                                    class="hidden text-muted-foreground sm:table-cell"
                                >
                                    {{ copy.shelf ?? '—' }}
                                </TableCell>
                                <TableCell class="pr-6">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            v-if="copy.status === 'available'"
                                            variant="ghost"
                                            size="icon-sm"
                                            aria-label="Зээлүүлэх"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    LoanController.create({
                                                        query: {
                                                            copy: copy.id,
                                                        },
                                                    })
                                                "
                                            >
                                                <BookUp />
                                            </Link>
                                        </Button>
                                        <ReturnLoanDialog
                                            v-if="loanOf(copy)"
                                            :loan="loanOf(copy)!"
                                        >
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                aria-label="Буцаах"
                                            >
                                                <Undo2 />
                                            </Button>
                                        </ReturnLoanDialog>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            aria-label="Засах"
                                            @click="openEdit(copy)"
                                        >
                                            <Pencil />
                                        </Button>
                                        <ConfirmAction
                                            :action="
                                                BookCopyController.destroy(
                                                    copy.id,
                                                )
                                            "
                                            title="Хувь устгах уу?"
                                            :description="`«${copy.inventory_no}» хувийг устгана. Зээлийн түүхтэй бол «Хасагдсан» төлөвт шилжүүлнэ үү.`"
                                        >
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                aria-label="Устгах"
                                                :disabled="!!copy.open_loan"
                                            >
                                                <Trash2 />
                                            </Button>
                                        </ConfirmAction>
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
                <CardTitle>Зээлийн түүх</CardTitle>
            </CardHeader>
            <CardContent class="px-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="pl-6">Уншигч</TableHead>
                            <TableHead>Хувь</TableHead>
                            <TableHead>Авсан</TableHead>
                            <TableHead>Буцаах</TableHead>
                            <TableHead>Буцаасан</TableHead>
                            <TableHead class="pr-6" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="loans.length === 0" :colspan="6">
                            Энэ номыг одоогоор хэн ч зээлээгүй байна.
                        </TableEmpty>
                        <TableRow v-for="loan in loans" :key="loan.id">
                            <TableCell class="pl-6">
                                <Link
                                    :href="
                                        MemberController.show(loan.member_id)
                                    "
                                    class="hover:underline"
                                >
                                    {{ loan.member?.name }}
                                </Link>
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
                            <TableCell class="tabular-nums">
                                {{ formatDate(loan.returned_at) }}
                            </TableCell>
                            <TableCell class="pr-6 text-right">
                                <DueBadge :loan="loan" />
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>

    <Dialog v-model:open="addOpen">
        <DialogContent class="sm:max-w-md">
            <form class="space-y-5" @submit.prevent="submitAdd">
                <DialogHeader>
                    <DialogTitle>Хувь нэмэх</DialogTitle>
                    <DialogDescription>
                        Бүртгэлийн дугаарыг хоосон орхивол автоматаар үүснэ.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid grid-cols-2 gap-4">
                    <FormField
                        label="Тоо"
                        for="quantity"
                        :error="addForm.errors.quantity"
                    >
                        <Input
                            id="quantity"
                            v-model="addForm.quantity"
                            type="number"
                            min="1"
                            max="50"
                        />
                    </FormField>
                    <FormField
                        label="Тавиур"
                        for="add-shelf"
                        :error="addForm.errors.shelf"
                    >
                        <Input
                            id="add-shelf"
                            v-model="addForm.shelf"
                            placeholder="А-12"
                        />
                    </FormField>
                </div>
                <FormField
                    label="Бүртгэлийн дугаар"
                    for="add-inventory"
                    :error="addForm.errors.inventory_no"
                    hint="Зөвхөн нэг хувь нэмэх үед оруулна."
                >
                    <Input
                        id="add-inventory"
                        v-model="addForm.inventory_no"
                        placeholder="Автомат"
                    />
                </FormField>
                <FormField
                    label="Тэмдэглэл"
                    for="add-note"
                    :error="addForm.errors.note"
                >
                    <Input id="add-note" v-model="addForm.note" />
                </FormField>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="addOpen = false"
                    >
                        Болих
                    </Button>
                    <Button type="submit" :disabled="addForm.processing">
                        Нэмэх
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="editOpen">
        <DialogContent class="sm:max-w-md">
            <form class="space-y-5" @submit.prevent="submitEdit">
                <DialogHeader>
                    <DialogTitle>Хувь засах</DialogTitle>
                    <DialogDescription v-if="editing?.status === 'borrowed'">
                        Зээлд байгаа хувийн төлөвийг «Буцаах» үйлдлээр өөрчилнө.
                    </DialogDescription>
                </DialogHeader>

                <FormField
                    label="Бүртгэлийн дугаар"
                    for="edit-inventory"
                    :error="editForm.errors.inventory_no"
                >
                    <Input
                        id="edit-inventory"
                        v-model="editForm.inventory_no"
                    />
                </FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField
                        label="Төлөв"
                        for="edit-status"
                        :error="editForm.errors.status"
                    >
                        <Select
                            v-model="editForm.status"
                            :disabled="editing?.status === 'borrowed'"
                        >
                            <SelectTrigger id="edit-status" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in editableStatuses"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>
                    <FormField
                        label="Тавиур"
                        for="edit-shelf"
                        :error="editForm.errors.shelf"
                    >
                        <Input id="edit-shelf" v-model="editForm.shelf" />
                    </FormField>
                </div>
                <FormField
                    label="Тэмдэглэл"
                    for="edit-note"
                    :error="editForm.errors.note"
                >
                    <Input id="edit-note" v-model="editForm.note" />
                </FormField>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="editOpen = false"
                    >
                        Болих
                    </Button>
                    <Button type="submit" :disabled="editForm.processing">
                        Хадгалах
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
