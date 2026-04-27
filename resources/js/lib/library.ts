import type { CopyStatus, Loan } from '@/types';

export const copyStatusLabels: Record<CopyStatus, string> = {
    available: 'Байгаа',
    borrowed: 'Зээлсэн',
    lost: 'Алдагдсан',
    damaged: 'Гэмтсэн',
    withdrawn: 'Хасагдсан',
};

export const copyStatusClasses: Record<CopyStatus, string> = {
    available:
        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300',
    borrowed:
        'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300',
    lost: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    damaged:
        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    withdrawn:
        'border-neutral-200 bg-neutral-100 text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400',
};

/** "2026-10-27" or "2026-10-27 14:05" -> "2026.10.27" */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return value.slice(0, 10).replaceAll('-', '.');
}

/** "2026-10-27 14:05" -> "2026.10.27 14:05" */
export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const time = value.slice(11, 16);

    return time ? `${formatDate(value)} ${time}` : formatDate(value);
}

/** Today's date in the browser as "YYYY-MM-DD". */
export function todayIso(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/** Whole days from today until the given "YYYY-MM-DD" date (negative if past). */
export function daysUntil(date: string): number {
    const [y, m, d] = date.slice(0, 10).split('-').map(Number);
    const [ty, tm, td] = todayIso().split('-').map(Number);
    const ms = Date.UTC(y, m - 1, d) - Date.UTC(ty, tm - 1, td);

    return Math.round(ms / 86_400_000);
}

export type DueState = 'returned' | 'overdue' | 'today' | 'soon' | 'ok';

export function dueState(loan: Loan): DueState {
    if (loan.returned_at) {
        return 'returned';
    }

    if (loan.is_overdue) {
        return 'overdue';
    }

    const days = daysUntil(loan.due_at);

    if (days <= 0) {
        return 'today';
    }

    return days <= 3 ? 'soon' : 'ok';
}

export function dueLabel(loan: Loan): string {
    switch (dueState(loan)) {
        case 'returned':
            return 'Буцаасан';
        case 'overdue':
            return `${loan.days_overdue} хоног хэтэрсэн`;
        case 'today':
            return 'Өнөөдөр буцаана';
        default:
            return `${daysUntil(loan.due_at)} хоног үлдсэн`;
    }
}
