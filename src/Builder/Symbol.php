<?php

namespace Theod\CloudVisionClient\Builder;

class Symbol
{
    public string $text;
    public bool $startOfTheWord;
    public float $symbolY;
    public float $symbolX;
    public bool $isFirstSymbolOfBlockLine;
    public bool $isLastSymbolOfBlockLine;

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return bool
     */
    public function isStartOfTheWord(): bool
    {
        return $this->startOfTheWord;
    }

    /**
     * @param bool $startOfTheWord
     */
    public function setStartOfTheWord(bool $startOfTheWord): void
    {
        $this->startOfTheWord = $startOfTheWord;
    }

    /**
     * @return float
     */
    public function getSymbolY(): float
    {
        return $this->symbolY;
    }

    /**
     * @param float $symbolY
     */
    public function setSymbolY(float $symbolY): void
    {
        $this->symbolY = $symbolY;
    }

    /**
     * @return float
     */
    public function getSymbolX(): float
    {
        return $this->symbolX;
    }

    /**
     * @param float $symbolX
     */
    public function setSymbolX(float $symbolX): void
    {
        $this->symbolX = $symbolX;
    }

    /**
     * @return bool
     */
    public function isFirstSymbolOfBlockLine(): bool
    {
        return $this->isFirstSymbolOfBlockLine;
    }

    /**
     * @param bool $isFirstSymbolOfBlockLine
     */
    public function setIsFirstSymbolOfBlockLine(bool $isFirstSymbolOfBlockLine): void
    {
        $this->isFirstSymbolOfBlockLine = $isFirstSymbolOfBlockLine;
    }

    /**
     * @return bool
     */
    public function isLastSymbolOfBlockLine(): bool
    {
        return $this->isLastSymbolOfBlockLine;
    }

    /**
     * @param bool $isLastSymbolOfBlockLine
     */
    public function setIsLastSymbolOfBlockLine(bool $isLastSymbolOfBlockLine): void
    {
        $this->isLastSymbolOfBlockLine = $isLastSymbolOfBlockLine;
    }
}