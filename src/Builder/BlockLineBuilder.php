<?php

namespace Theod\CloudVisionClient\Builder;

class BlockLineBuilder
{
    private array $block;
    private array $lines;
    private array $word;

    /**
     * @return array
     */
    public function getBlock(): array
    {
        return $this->block;
    }

    /**
     * @param array $block
     */
    public function setBlock(array $block): void
    {
        $this->block = $block;
    }

    /**
     * @return array
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * @param array $lines
     */
    public function setLines(array $lines): void
    {
        $this->line = $lines;
    }

    /**
     * @return array
     */
    public function getWord(): array
    {
        return $this->word;
    }

    /**
     * @param array $word
     */
    public function setWord(array $word): void
    {
        $this->word = $word;
    }

    /**
     * @param Line $line
     * @param array $symbol
     * @param int $symbolKey
     * @param float $symbolMidYPoint
     * @param float $symbolMidXPoint
     * @return void
     */
    public function addSymbolToNewLine(Line $line, array $symbol, int $symbolKey, float $symbolMidYPoint, float $symbolMidXPoint): void
    {
        $line->pushContent(["text" => $symbol['text'], "startOfTheWord" => $symbolKey === 0, "symbolY" => $symbolMidYPoint, "symbolX" => $symbolMidXPoint, "isFirstSymbolOfBlockLine" => true, "isLastSymbolOfBlockLine" => true]);
        $this->lines[] = $line;
    }

    /**
     * @param Line $line
     * @param array $symbol
     * @param int $symbolKey
     * @param float $symbolMidYPoint
     * @param float $symbolMidXPoint
     * @return void
     */
    public function addSymbolToExistingLine(Line $line, array $symbol, int $symbolKey, float $symbolMidYPoint, float $symbolMidXPoint): void
    {
        $this->lines[count($this->lines) - 1]["isLastSymbolOfBlockLine"] = false;
        $line->pushContent(["text" => $symbol['text'], "startOfTheWord" => $symbolKey === 0, "symbolY" => $symbolMidYPoint, "symbolX" => $symbolMidXPoint, "isFirstSymbolOfBlockLine" => false, "isLastSymbolOfBlockLine" => true]);
    }
}