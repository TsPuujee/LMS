<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Member } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Уншигчид', href: MemberController.index() }],
    },
});

const props = defineProps<{
    member: Member | null;
}>();

const form = useForm({
    member_no: props.member?.member_no ?? '',
    name: props.member?.name ?? '',
    phone: props.member?.phone ?? '',
    email: props.member?.email ?? '',
    address: props.member?.address ?? '',
    note: props.member?.note ?? '',
    is_active: props.member?.is_active ?? true,
});

function submit() {
    form.submit(
        props.member
            ? MemberController.update(props.member.id)
            : MemberController.store(),
    );
}
</script>

<template>
    <Head :title="member ? 'Уншигч засах' : 'Уншигч нэмэх'" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="member ? 'Уншигч засах' : 'Шинэ уншигч'"
            :description="member ? member.name : 'Номын сангийн үнэмлэх олгох'"
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-5 sm:grid-cols-2">
                <FormField
                    label="Овог нэр"
                    for="name"
                    :error="form.errors.name"
                    required
                >
                    <Input
                        id="name"
                        v-model="form.name"
                        v-focus
                        required
                        placeholder="Б. Батболд"
                    />
                </FormField>

                <FormField
                    label="Үнэмлэхний дугаар"
                    for="member_no"
                    :error="form.errors.member_no"
                    hint="Хоосон орхивол M-00001 хэлбэрээр автоматаар үүснэ."
                >
                    <Input
                        id="member_no"
                        v-model="form.member_no"
                        placeholder="Автомат"
                    />
                </FormField>

                <FormField label="Утас" for="phone" :error="form.errors.phone">
                    <Input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        inputmode="tel"
                    />
                </FormField>

                <FormField
                    label="И-мэйл"
                    for="email"
                    :error="form.errors.email"
                >
                    <Input id="email" v-model="form.email" type="email" />
                </FormField>

                <FormField
                    class="sm:col-span-2"
                    label="Хаяг"
                    for="address"
                    :error="form.errors.address"
                >
                    <Input id="address" v-model="form.address" />
                </FormField>

                <FormField
                    class="sm:col-span-2"
                    label="Тэмдэглэл"
                    for="note"
                    :error="form.errors.note"
                >
                    <Textarea id="note" v-model="form.note" rows="2" />
                </FormField>

                <div class="flex items-center gap-2 sm:col-span-2">
                    <Checkbox
                        id="is_active"
                        :model-value="form.is_active"
                        @update:model-value="
                            (v) => (form.is_active = v === true)
                        "
                    />
                    <Label for="is_active"> Идэвхтэй — ном зээлж болно </Label>
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    Хадгалах
                </Button>
                <Button variant="ghost" as-child>
                    <Link
                        :href="
                            member
                                ? MemberController.show(member.id)
                                : MemberController.index()
                        "
                    >
                        Болих
                    </Link>
                </Button>
            </div>
        </form>
    </div>
</template>
