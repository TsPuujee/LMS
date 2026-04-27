<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import FormField from '@/components/FormField.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { copyStatusLabels } from '@/lib/library';
import type { CopyStatus, Loan } from '@/types';

const props = defineProps<{
    loan: Loan;
}>();

const statuses: CopyStatus[] = ['available', 'damaged', 'lost'];

const open = ref(false);
const form = useForm<{ status: CopyStatus; note: string }>({
    status: 'available',
    note: '',
});

function submit() {
    form.submit(LoanController.returnCopy(props.loan.id), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-md">
            <form class="space-y-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Ном буцаах</DialogTitle>
                    <DialogDescription>
                        «{{ loan.copy?.book?.title }}» ({{
                            loan.copy?.inventory_no
                        }}) — {{ loan.member?.name }}
                    </DialogDescription>
                </DialogHeader>

                <FormField
                    label="Номын байдал"
                    for="return-status"
                    :error="form.errors.status"
                >
                    <Select v-model="form.status">
                        <SelectTrigger id="return-status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="status in statuses"
                                :key="status"
                                :value="status"
                            >
                                {{
                                    status === 'available'
                                        ? 'Хэвийн — тавиурт буцаана'
                                        : copyStatusLabels[status]
                                }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>

                <FormField
                    label="Тэмдэглэл"
                    for="return-note"
                    :error="form.errors.note"
                >
                    <Textarea
                        id="return-note"
                        v-model="form.note"
                        rows="2"
                        placeholder="Жишээ нь: хавтас нь урагдсан"
                    />
                </FormField>

                <InputError
                    :message="
                        (form.errors as Record<string, string | undefined>).loan
                    "
                />

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">Болих</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="form.processing">
                        Буцааж авах
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
