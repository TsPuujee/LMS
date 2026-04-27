<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/BookController';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Book, Category } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Ном', href: BookController.index() }],
    },
});

const props = defineProps<{
    book: Book | null;
    categories: Pick<Category, 'id' | 'name'>[];
}>();

const form = useForm({
    category_id: props.book ? String(props.book.category_id) : '',
    title: props.book?.title ?? '',
    author: props.book?.author ?? '',
    isbn: props.book?.isbn ?? '',
    publisher: props.book?.publisher ?? '',
    published_year: props.book?.published_year ?? '',
    language: props.book?.language ?? 'Монгол',
    pages: props.book?.pages ?? '',
    description: props.book?.description ?? '',
    copies: 1,
});

function submit() {
    form.transform((data) => ({
        ...data,
        published_year: data.published_year === '' ? null : data.published_year,
        pages: data.pages === '' ? null : data.pages,
    })).submit(
        props.book
            ? BookController.update(props.book.id)
            : BookController.store(),
    );
}
</script>

<template>
    <Head :title="book ? 'Ном засах' : 'Ном нэмэх'" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="book ? 'Ном засах' : 'Шинэ ном'"
            :description="
                book
                    ? book.title
                    : 'Номын мэдээлэл болон хэдэн хувь байгааг оруулна уу'
            "
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-5 sm:grid-cols-2">
                <FormField
                    class="sm:col-span-2"
                    label="Номын нэр"
                    for="title"
                    :error="form.errors.title"
                    required
                >
                    <Input id="title" v-model="form.title" v-focus required />
                </FormField>

                <FormField
                    label="Зохиогч"
                    for="author"
                    :error="form.errors.author"
                    required
                >
                    <Input id="author" v-model="form.author" required />
                </FormField>

                <FormField
                    label="Ангилал"
                    for="category_id"
                    :error="form.errors.category_id"
                    required
                >
                    <Select v-model="form.category_id">
                        <SelectTrigger id="category_id" class="w-full">
                            <SelectValue placeholder="Сонгох" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="category in categories"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>

                <FormField
                    label="ISBN"
                    for="isbn"
                    :error="form.errors.isbn"
                    hint="10 эсвэл 13 оронтой. Зураас оруулж болно."
                >
                    <Input
                        id="isbn"
                        v-model="form.isbn"
                        placeholder="978-99929-0-000-0"
                    />
                </FormField>

                <FormField
                    label="Хэвлэлийн газар"
                    for="publisher"
                    :error="form.errors.publisher"
                >
                    <Input id="publisher" v-model="form.publisher" />
                </FormField>

                <FormField
                    label="Хэвлэгдсэн он"
                    for="published_year"
                    :error="form.errors.published_year"
                >
                    <Input
                        id="published_year"
                        v-model="form.published_year"
                        type="number"
                        min="1000"
                        inputmode="numeric"
                    />
                </FormField>

                <FormField
                    label="Хэл"
                    for="language"
                    :error="form.errors.language"
                >
                    <Input id="language" v-model="form.language" />
                </FormField>

                <FormField
                    label="Хуудасны тоо"
                    for="pages"
                    :error="form.errors.pages"
                >
                    <Input
                        id="pages"
                        v-model="form.pages"
                        type="number"
                        min="1"
                        inputmode="numeric"
                    />
                </FormField>

                <FormField
                    v-if="!book"
                    label="Хувийн тоо"
                    for="copies"
                    :error="form.errors.copies"
                    hint="Бүртгэлийн дугаар INV-000001 хэлбэрээр автоматаар үүснэ."
                >
                    <Input
                        id="copies"
                        v-model="form.copies"
                        type="number"
                        min="0"
                        max="50"
                        inputmode="numeric"
                    />
                </FormField>

                <FormField
                    class="sm:col-span-2"
                    label="Тайлбар"
                    for="description"
                    :error="form.errors.description"
                >
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                    />
                </FormField>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    Хадгалах
                </Button>
                <Button variant="ghost" as-child>
                    <Link
                        :href="
                            book
                                ? BookController.show(book.id)
                                : BookController.index()
                        "
                    >
                        Болих
                    </Link>
                </Button>
            </div>
        </form>
    </div>
</template>
