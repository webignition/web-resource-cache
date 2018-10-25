<?php

namespace App\Services;

class ArrayCollection
{
    /**
     * @var array
     */
    private $collection;

    public function __construct(array $collection = [])
    {
        $this->collection = $collection;
    }

    public function get(): array
    {
        return $this->collection;
    }
}
