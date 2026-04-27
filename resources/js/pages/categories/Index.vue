<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import BookController from '@/actions/App/Http/Controllers/BookController';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import ConfirmAction from '@/components/ConfirmAction.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
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
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import type { Category } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Ангилал', href: CategoryController.index() }],
    },
});

defineProps<{
    categories: Category[];
}>();

const dialogOpen = ref(false);
const editing = ref<Category | null>(null);
const form = useForm({ name: '', description: '' });

function openCreate() {
    editing.value = null;
    form.clearErrors();
    form.defaults({ name: '', description: '' });
    form.reset();
    dialogOpen.value = true;
}

function openEdit(category: Category) {
    editing.value = category;
    form.clearErrors();
    form.defaults({
        name: category.name,
        description: category.description ?? '',
    });
    form.reset();
    dialogOpen.value = true;
}

function submit() {
    const action = editing.value
        ? CategoryController.update(editing.value.id)
        : CategoryController.store();

    form.submit(action, {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    });
}
</script>

<template>
    <Head title="Ангилал" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Номын ангилал"
            description="Номыг сэдвээр нь бүлэглэнэ"
        >
            <Button @click="openCreate"><Plus /> Ангилал нэмэх</Button>
        </PageHeader>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="pl-4">Нэр</TableHead>
                        <TableHead class="hidden md:table-cell">
                            Тайлбар
                        </TableHead>
                        <TableHead class="text-right">Номын тоо</TableHead>
                        <TableHead class="w-24" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="categories.length === 0" :colspan="4">
                        Ангилал бүртгэгдээгүй байна.
                    </TableEmpty>
                    <TableRow v-for="category in categories" :key="category.id">
                        <TableCell class="pl-4 font-medium">
                            {{ category.name }}
                        </TableCell>
                        <TableCell
                            class="hidden max-w-md truncate text-muted-foreground md:table-cell"
                        >
                            {{ category.description }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            <Link
                                :href="
                                    BookController.index({
                                        query: { category: category.id },
                                    })
                                "
                                class="hover:underline"
                            >
                                {{ category.books_count }}
                            </Link>
                        </TableCell>
                        <TableCell class="pr-4 text-right">
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Засах"
                                    @click="openEdit(category)"
                                >
                                    <Pencil />
                                </Button>
                                <ConfirmAction
                                    :action="
                                        CategoryController.destroy(category.id)
                                    "
                                    title="Ангилал устгах уу?"
                                    :description="`«${category.name}» ангиллыг устгана. Номтой ангиллыг устгах боломжгүй.`"
                                >
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label="Устгах"
                                        :disabled="
                                            (category.books_count ?? 0) > 0
                                        "
                                    >
                                        <Trash2 />
                                    </Button>
                                </ConfirmAction>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <form class="space-y-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'Ангилал засах' : 'Шинэ ангилал' }}
                    </DialogTitle>
                    <DialogDescription>
                        Ангиллын нэр давхардахгүй байх ёстой.
                    </DialogDescription>
                </DialogHeader>

                <FormField
                    label="Нэр"
                    for="name"
                    :error="form.errors.name"
                    required
                >
                    <Input id="name" v-model="form.name" v-focus required />
                </FormField>

                <FormField
                    label="Тайлбар"
                    for="description"
                    :error="form.errors.description"
                >
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                    />
                </FormField>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="dialogOpen = false"
                    >
                        Болих
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        Хадгалах
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
