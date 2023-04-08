<?php

namespace Theod\CloudVisionClient\Builder;

use Theod\CloudVisionClient\Parser\ReceiptParserResponse;
use Theod\CloudVisionClient\Utilities\ReceiptParserUtility;

class ReceiptParserBuilder
{
    private const SAME_LINE_THRESHOLD_INDICATOR = 30;
    private array $block;
    private array $lines;
    private array $linesComposed;
    private array $resultLines;
    private string $blocksOrientation;
    private ReceiptParserUtility $receiptParserUtility;


    /**
     * @param ReceiptParserUtility $receiptParserUtility
     * @param string $blocksOrientation
     * @return static
     */
    public static function init(
        ReceiptParserUtility $receiptParserUtility,
        string $blocksOrientation = ReceiptParserUtility::BLOCK_ORIENTATION_ZERO_DEG
    ): self {
        $builder = new self();
        $builder->receiptParserUtility = $receiptParserUtility;
        $builder->blocksOrientation = $blocksOrientation;

        return $builder;
    }

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

    /**
     * @return $this
     */
    public function buildBlockFullLinesFromLineGroupsOfSymbols(): ReceiptParserBuilder
    {
        foreach ($this->getLines() as $line) {
            $builderCompose = new BlockLineCompose();

            foreach($line->getContent() as $builderSymbol) {
                /**
                 * @var Symbol $builderSymbol
                 */
                if (true === $builderSymbol->isFirstSymbolOfBlockLine()) {
                    $builderCompose->setBlockLineStartX($builderSymbol->getSymbolX());
                    $builderCompose->setBlockLineStartY($builderSymbol->getSymbolY());
                }

                if (true === $builderSymbol->isLastSymbolOfBlockLine()) {
                    $builderCompose->setBlockLineEndY($builderSymbol->getSymbolY());
                    $builderCompose->setBlockLineEndX($builderSymbol->getSymbolX());
                }

                if (true === $builderSymbol->isStartOfTheWord() && false === $builderSymbol->isFirstSymbolOfBlockLine()) {
                    $description = $builderCompose->getDescription() . " " . $builderSymbol->getText();
                } else {
                    $description = $builderCompose->getDescription() . $builderSymbol->getText();
                }

                $builderCompose->setDescription($description);
            }

            $this->addLineComposed($builderCompose);
        }

        return $this;
    }

    /**
     * @return self
     */
    public function buildResultLinesFromBlockFullLines(): self
    {
        $linesComposedTempBase = $this->getLinesComposed();
        $counter = 0;

        foreach ($linesComposedTempBase as $blockKeyA=>$blockA) {
            $linesComposedTemp = $linesComposedTempBase;

            /**
             * @var BlockLineCompose $blockA
             * @var BlockLineCompose $blockB
             */
            foreach ($linesComposedTemp as $blockKeyB=>$blockB) {
                if ($blockKeyA == $blockKeyB) {
                    continue;
                }

                if (abs($blockA->getBlockLineStartY() - $blockB->getBlockLineEndY()) <= self::SAME_LINE_THRESHOLD_INDICATOR) { // Means  that A is at the same line as B
                    $lineY = ($blockA->getBlockLineStartY() + $blockB->getBlockLineEndY()) / 2;
                    $lineStartX = $blockB->getBlockLineStartX();
                    $lineEndX = $blockA->getBlockLineEndX();
                    $resultLine = $this->getResultLineByKeyOrNull($counter);


                    if (null !== $resultLine) {
                        if ($blockA->getBlockLineStartX() > $blockB->getBlockLineStartX()) {
                            if (ReceiptParserUtility::BLOCK_ORIENTATION_ZERO_DEG === $this->blocksOrientation) {
                                $description = $blockB->getDescription() . " " . $resultLine->getText();
                            } else {
                                $description = $resultLine->getText() . " " . $blockB->getDescription();
                            }
                        } else {
                            if (ReceiptParserUtility::BLOCK_ORIENTATION_ZERO_DEG === $this->blocksOrientation) {
                                $description = $resultLine->getText() . " " . $blockB->getDescription();
                            } else {
                                $description = $blockB->getDescription() . " " . $resultLine->getText();
                            }
                        }
                    } else {
                        if ($blockA->getBlockLineStartX() > $blockB->getBlockLineStartX()) {
                            if (ReceiptParserUtility::BLOCK_ORIENTATION_ZERO_DEG === $this->blocksOrientation) {
                                $description = $blockB->getDescription() . " " . $blockA->getDescription();
                            } else {
                                $description = $blockB->getDescription() . " " . $blockA->getDescription();
                            }
                        } else {
                            if (ReceiptParserUtility::BLOCK_ORIENTATION_ZERO_DEG === $this->blocksOrientation) {
                                $description = $blockA->getDescription() . " " . $blockB->getDescription();
                            } else {
                                $description = $blockB->getDescription() . " " . $blockA->getDescription();
                            }
                        }
                    }

                    $resultLine = new ResultLine();
                    $resultLine->setText($description);
                    $resultLine->setLineEndX($lineEndX);
                    $resultLine->setLineStartX($lineStartX);
                    $resultLine->setLineY($lineY);

                    unset($linesComposedTempBase[$blockKeyA]);
                    unset($linesComposedTempBase[$blockKeyB]);

                    $this->addResultLine($resultLine);
                }
            }
            $counter++;
        }

        // Loop through the lines which occupy full line itself within the document
        // and append them in the results
        $this->addSingleBlockLinesToResult($linesComposedTempBase);

        return $this;
    }

    /**
     * @param array $blocks
     * @param string $blocksOrientation
     * @param float $yThreshold
     * @param float $currentBlockLineY
     * @return ReceiptParserBuilder
     */
    public function buildLineGroupsOfSymbolsFromBlocks(
        array $blocks,
        string $blocksOrientation,
        float $yThreshold,
        float $currentBlockLineY
    ): ReceiptParserBuilder {

        foreach ($blocks as $block) {
            $this->setBlock($block);
            $paragraphs = $this->receiptParserUtility->getParagraphsFromBlock($block);

            foreach ($paragraphs as $paragraphKey => $paragraph) {
                $words = $this->receiptParserUtility->getWordsFromParagraph($paragraph);

                foreach ($words as $wordKey => $word) {
                    $symbols = $this->receiptParserUtility->getSymbolsFromWord($word);

                    foreach ($symbols as $symbolKey => $symbol) {
                        // Calculate the average of symbols' left and right boundaries y coordinates for top and bottom side
                        $symbolBounds = $this->receiptParserUtility->getBoundsForSymbolByOrientation($symbol, $blocksOrientation);

                        // Calculate the point in the middle of the top and bottom Y coordinates of the symbol
                        $symbolMidYPoint = $this->receiptParserUtility->getMiddlePointOfYCoordinateFromSymbolBounds($symbolBounds);
                        $symbolMidXPoint = $this->receiptParserUtility->getMiddlePointOfXCoordinateFromSymbolBounds($symbolBounds);

                        // Compose line groups of symbols
                        $symbolMeta = new Symbol();
                        $symbolMeta->setText($symbol['text']);

                        if (0 === $symbolKey) {
                            $symbolMeta->setStartOfTheWord(true);
                        } else {
                            $symbolMeta->setStartOfTheWord(false);
                        }

                        $symbolMeta->setSymbolY($symbolMidYPoint);
                        $symbolMeta->setSymbolX($symbolMidXPoint);
                        $symbolMeta->setIsLastSymbolOfBlockLine(true);

                        if ($this->receiptParserUtility->isFirstElementOfTheBlock($paragraphKey, $wordKey, $symbolKey) ||
                            $this->receiptParserUtility->assumeNewBlockLine($symbolMidYPoint, $currentBlockLineY, $yThreshold)
                        ) {
                            $line = new Line();
                            $currentBlockLineY = $symbolMidYPoint;
                            $symbolMeta->setIsFirstSymbolOfBlockLine(true);
                            $line->pushSymbol($symbolMeta);
                            $this->addLine($line);
                        } else {
                            $symbolMeta->setIsFirstSymbolOfBlockLine(false);

                            if (!isset($line) || false === $line instanceof Line) {
                                $line = new Line();
                            }

                            $line->pushSymbol($symbolMeta);
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param array $linesComposed
     * @return void
     */
    private function addSingleBlockLinesToResult(array $linesComposed): void
    {
        foreach ($linesComposed as $blockA) {
            $resultLine = new ResultLine();
            $resultLine->setText($blockA->getDescription());
            $resultLine->setLineEndX($blockA->getBlockLineEndX());
            $resultLine->setLineStartX($blockA->getBlockLineStartX());
            $resultLine->setLineY(($blockA->getBlockLineStartY() + $blockA->getBlockLineEndY()) / 2);

            $this->addResultLine($resultLine);
        }
    }
}
