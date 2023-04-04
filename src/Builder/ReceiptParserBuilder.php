<?php

namespace Theod\CloudVisionClient\Builder;

class ReceiptParserBuilder
{
    private array $block;
    private array $lines;
    private array $linesComposed;
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

        foreach ($resultLines as $item) {
            $resultLine = new ResultLine();
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
     * @return array
     */
    public function getLinesComposed(): array
    {
        return $this->linesComposed;
    }

    /**
     * @param Line $line
     * @return void
     */
    public function addLine(Line $line): void
    {
        $this->lines[] = $line;
    }

    /**
     * @param BlockLineCompose $lineCompose
     * @return void
     */
    public function addLineComposed(BlockLineCompose $lineCompose): void
    {
        $this->linesComposed[] = $lineCompose;
    }

    /**
     * @param ResultLine $resultLine
     * @return void
     */
    public function addResultLine(ResultLine $resultLine): void
    {
        $this->resultLines[] = $resultLine;
    }
}
