<?php

namespace Sslcommerz\Laravel\DTOs;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Makes a DTO behave like an array for better developer experience.
 */
trait ArrayableDTO
{
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->{$offset}) || isset($this->rawResponse[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset} ?? $this->rawResponse[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // DTOs are readonly/immutable
    }

    public function offsetUnset(mixed $offset): void
    {
        // DTOs are readonly/immutable
    }

    public function toArray(): array
    {
        return array_merge($this->rawResponse, get_object_vars($this));
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
