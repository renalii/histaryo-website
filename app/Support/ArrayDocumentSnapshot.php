<?php

namespace App\Support;

final class ArrayDocumentSnapshot
{
    public function __construct(private string $id, private array $data) {}

    public function id(): string
    {
        return $this->id;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function exists(): bool
    {
        return true;
    }
}
