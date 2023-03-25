<?php

namespace Theod\CloudVision\Objects;

class Uri
{
    /**
     * @var string
     */
    protected string $uri;

    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->uri;
    }
}