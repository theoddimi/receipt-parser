<?php

namespace Theod\CloudVisionClient\Builder;

class Line
{
    private array $content;
    public Symbol $symbol;

    /**
     * @return Symbol
     */
    public function getSymbol(): Symbol
    {
        return $this->symbol;
    }

    /**
     * @param Symbol $symbol
     */
    public function setSymbol(Symbol $symbol): void
    {
        $this->symbol = $symbol;
    }

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

    public function pushContent(Symbol $content)
    {
        $this->content[] = $content;
    }
}