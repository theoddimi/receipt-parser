<?php

namespace Theod\CloudVisionClient\Collections;

abstract class CloudVisionCollection
{
    protected array $items = [];

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }
}