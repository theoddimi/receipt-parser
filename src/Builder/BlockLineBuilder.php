<?php

namespace Theod\CloudVisionClient\Builder;

class BlockLineBuilder
{
    public array $block;
    public array $word;
    public array $lines;
    public array $linesComposed;
    public array $resultLines;

    /**
     * @return array
     */
    public function getResultLines(): array
    {
        return $this->resultLine;
    }

    /**
     * @param int $key
     * @return ResultLine|null
     */
    public function getResultLineByKeyOrNull(int $key): ?ResultLine
    {
        if (isset($this->resultLines[$key])) {
            return $this->resultLines[$key];
        }

        return null;
    }

    /**
     * @param array $resultLine
     */
    public function setResultLines(array $resultLine): void
    {
        $this->resultLine = $resultLine;
    }

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
     * @return array
     */
    public function getLinesComposed(): array
    {
        return $this->linesComposed;
    }

    /**
     * @param array $linesComposed
     */
    public function setLinesComposed(array $linesComposed): void
    {
        $this->linesComposed = $linesComposed;
    }


//
//    /**
//     * @param Line $line
//     * @param array $symbol
//     * @param int $symbolKey
//     * @param float $symbolMidYPoint
//     * @param float $symbolMidXPoint
//     * @return void
//     */
//    public function addNewLine(Line $line, array $symbol, int $symbolKey, float $symbolMidYPoint, float $symbolMidXPoint): void
//    {
//        $line->pushContent(["text" => $symbol['text'], "startOfTheWord" => $symbolKey === 0, "symbolY" => $symbolMidYPoint, "symbolX" => $symbolMidXPoint, "isFirstSymbolOfBlockLine" => true, "isLastSymbolOfBlockLine" => true]);
//        $this->lines[] = $line;
//    }
//
//    /**
//     * @param Line $line
//     * @param array $symbol
//     * @param int $symbolKey
//     * @param float $symbolMidYPoint
//     * @param float $symbolMidXPoint
//     * @return void
//     */
//    public function addSymbolToExistingLine(Line $line, array $symbol, int $symbolKey, float $symbolMidYPoint, float $symbolMidXPoint): void
//    {
//        $this->lines[count($this->lines) - 1]["isLastSymbolOfBlockLine"] = false;
//        $line->pushContent(["text" => $symbol['text'], "startOfTheWord" => $symbolKey === 0, "symbolY" => $symbolMidYPoint, "symbolX" => $symbolMidXPoint, "isFirstSymbolOfBlockLine" => false, "isLastSymbolOfBlockLine" => true]);
//    }

    public function addLine(Line $line)
    {
        $this->lines[] = $line;
    }

    public function addLineComposed(BlockLineCompose $lineCompose)
    {
        $this->linesComposed[] = $lineCompose;
    }

    public function addResultLine(ResultLine $resultLine)
    {
        $this->resultLines[] = $resultLine;
    }
}
