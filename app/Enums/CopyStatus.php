<?php

namespace App\Enums;

enum CopyStatus: string
{
    case Available = 'available';
    case Borrowed = 'borrowed';
    case Lost = 'lost';
    case Damaged = 'damaged';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Байгаа',
            self::Borrowed => 'Зээлсэн',
            self::Lost => 'Алдагдсан',
            self::Damaged => 'Гэмтсэн',
            self::Withdrawn => 'Хасагдсан',
        };
    }

    /**
     * Statuses a librarian may set by hand. "Borrowed" is only ever set by
     * the loan workflow so it always matches an open loan.
     *
     * @return list<self>
     */
    public static function manual(): array
    {
        return [self::Available, self::Lost, self::Damaged, self::Withdrawn];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(bool $manualOnly = false): array
    {
        $cases = $manualOnly ? self::manual() : self::cases();

        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            $cases,
        );
    }
}
