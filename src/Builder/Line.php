<?php

namespace Theod\CloudVisionClient\Builder;

class Line
{
    private array $content = [];
    public Symbol $symbol;

    public function __construct() {}

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

    public function pushSymbol(Symbol $content)
    {
        if (count($this->content) > 0) {
            $lastSymbol = $this->content[count($this->content) - 1];
            /**
             * @var Symbol $lastSymbol
             */
            $lastSymbol->setIsLastSymbolOfBlockLine(false);
        }

        $this->content[] = $content;
    }
}