<?php

namespace App\Model\Response;

interface ResponseInterface
{
    /**
     * Return an array representation that contains only scalar values
     *
     * @return array
     */
    public function toScalarArray(): array;
}
