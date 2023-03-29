<?php

namespace Theod\CloudVisionClient\Builder;

class Line
{
    private array $content;

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @param array $content
     */
    public function setContent(array $content): void
    {
        $this->content = $content;
    }

    public function pushContent(array $content)
    {
        $this->content[] = $content;
    }
}