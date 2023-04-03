<?php

namespace Theod\CloudVisionClient\Builder;

class BlockLineBuilder
{
    public array $block;
    public array $word;
    public array $lines;
    public array $linesComposed;
    private array $resultLines;

    /**
     * @return array
     */
    public function getResultLines(): array
    {
        return $this->resultLines;
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
     * @param array $resultLines
     */
    public function setResultLines(array $resultLines): void
    {
        $res = [];
//        dd($resultLines);
        foreach ($resultLines as $item) {
            $resultLine = new ResultLine($item);
            $resultLine->setText($item['text']);
            $resultLine->setLineY($item['lineY']);
            $resultLine->setLineStartX($item['lineStartX']);
            $resultLine->setLineEndX($item['lineEndX']);

            $res[] = $resultLine;
        }

        $this->resultLines = $res;
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
