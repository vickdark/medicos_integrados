<?php

namespace App\Concerns;

trait HasEnumOptions
{
    /**
     * Get the enum cases as value/label pairs for select inputs.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => $case->toOption(), self::cases());
    }

    /**
     * Get the case as a value/label pair.
     *
     * @return array{value: string, label: string}
     */
    public function toOption(): array
    {
        return ['value' => $this->value, 'label' => $this->label()];
    }
}
