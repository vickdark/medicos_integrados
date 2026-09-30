<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum TurnStatus: string
{
    use HasEnumOptions;

    case Waiting = 'waiting';
    case Called = 'called';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'En espera',
            self::Called => 'En atención',
            self::Done => 'Atendido',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Whether the turn is still in the queue.
     */
    public function isActive(): bool
    {
        return $this === self::Waiting || $this === self::Called;
    }

    /**
     * Statuses the turn may move to from this one.
     *
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Waiting => [self::Called, self::Cancelled],
            self::Called => [self::Done, self::Waiting, self::Cancelled],
            self::Done, self::Cancelled => [],
        };
    }

    public function canMoveTo(self $status): bool
    {
        return in_array($status, $this->nextStatuses(), true);
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Waiting, self::Called];
    }
}
