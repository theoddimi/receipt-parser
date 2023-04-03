<?php

namespace Theod\CloudVisionClient\Processor;

use Illuminate\Http\Client\Response;
use Theod\CloudVisionClient\Builder\BlockLineBuilder;
use Theod\CloudVisionClient\Builder\BlockLineCompose;
use Theod\CloudVisionClient\Builder\Line;
use Theod\CloudVisionClient\Builder\ResultLine;
use Theod\CloudVisionClient\Builder\Symbol;
use Theod\CloudVisionClient\Builder\WordBuilder;
use Theod\CloudVisionClient\Parser\ReceiptParserRequest;
use Theod\CloudVisionClient\Parser\ReceiptParserResponse;
use Theod\CloudVisionClient\Utilities\ReceiptParserUtility;
use Theod\CloudVisionClient\Processor\Contracts\ReceiptParserProcessorInterface;
use Theod\CloudVisionClient\Services\CloudVisionService;
use Theod\CloudVisionClient\Objects\Uri;

class ReceiptParserProcessor extends Processor implements ReceiptParserProcessorInterface
{
    private const CONTENT_TYPE = 'application/json';

    private Response $cloudVisionResponse;

    public function __construct(
        private readonly CloudVisionService $cloudVisionService,
        private readonly ReceiptParserUtility $receiptParserUtility
    ){}


    public function run()
    {
        // Init variables
        $yThreshold = 30;
        $thresholdIndicatorForSameLine = 30;
        $currentBlockLineY = -1;

        $this->start();

        $receiptParserRequest = new ReceiptParserRequest();
        $receiptParserRequest->addSourceUriToBody($this->getSourceUriToProcess());

        $httpResponse = $this->cloudVisionService->postImageAnnotateWithRequest($receiptParserRequest);
        $receiptParserResponse = ReceiptParserResponse::createFromHttpResponse($httpResponse);

        // Find orientation of blocks returned
        $blocksOrientation = $this->receiptParserUtility->specifyBlocksOrientationFromResponse($receiptParserResponse);
        $receiptParserResponse->setBlocksOrientation($blocksOrientation);
        $response = $receiptParserResponse->toArray();
        $blocks = $this->receiptParserUtility->retrieveBlocksFromDecodedResponse($response);

        $blockLine = new BlockLineBuilder();

        // Start blocks looping
        foreach ($blocks as $blockKey=>$block) {
            $blockLine->setBlock($block);
            $line = 0;
            $paragraphs = $this->receiptParserUtility->getParagraphsFromBlock($block);

            foreach ($paragraphs as $paragraphKey => $paragraph) {
                $words = $this->receiptParserUtility->getWordsFromParagraph($paragraph);

                foreach ($words as $wordKey => $word) {
                    $blockLine->setWord($word);
                    $symbols = $this->receiptParserUtility->getSymbolsFromWord($word);

                    foreach ($symbols as $symbolKey => $symbol) {
                        ################ Calculate current symbol's y and check line status #########################
                        // Calculate the average of symbols' left and right boundaries y coordinates for top and bottom side
                        if (ReceiptParserUtility::BLOCK_ORIENTATION_ZERO_DEG === $blocksOrientation) {
                            $symbolTopYBoundAvg = $this->receiptParserUtility->calculateTopSideYBoundAverageForSymbolAndOrientationZero($symbol);
                            $symbolBottomYBoundAvg = $this->receiptParserUtility->calculateBottomSideYBoundAverageForSymbolAndOrientationZero($symbol);
                            $symbolLeftXBoundAvg = $this->receiptParserUtility->calculateLeftSideXBoundAverageForSymbolAndOrientationZero($symbol);
                            $symbolRightXBoundAvg = $this->receiptParserUtility->calculateRightSideXBoundAverageForSymbolAndOrientationZero($symbol);
                        } else {
                            $symbolTopYBoundAvg = $this->receiptParserUtility->calculateTopSideYBoundAverageForSymbolAndOrientationNinety($symbol);
                            $symbolBottomYBoundAvg = $this->receiptParserUtility->calculateBottomSideYBoundAverageForSymbolAndOrientationNinety($symbol);
                            $symbolLeftXBoundAvg = $this->receiptParserUtility->calculateLeftSideXBoundAverageForSymbolAndOrientationNinety($symbol);
                            $symbolRightXBoundAvg = $this->receiptParserUtility->calculateRightSideXBoundAverageForSymbolAndOrientationNinety($symbol);
                        }

                        // Calculate the point in the middle of the top and bottom Y coordinates of the symbol
                        $symbolMidYPoint = $this->receiptParserUtility->calculateMiddleYPointForSymbolBoundsY(
                            $symbolTopYBoundAvg,
                            $symbolBottomYBoundAvg
                        );

                        $symbolMidXPoint = $this->receiptParserUtility->calculateMiddleXPointForSymbolBoundsX(
                            $symbolLeftXBoundAvg,
                            $symbolRightXBoundAvg
                        );

                        ################## Compose block line words, symbol by symbol #############
                        $symbolMeta = new Symbol();
                        $symbolMeta->setText($symbol['text']);
                        $symbolMeta->setStartOfTheWord(0 === $symbolKey);
                        $symbolMeta->setSymbolY($symbolMidYPoint);
                        $symbolMeta->setSymbolX($symbolMidXPoint);
                        $symbolMeta->setIsLastSymbolOfBlockLine(true);

                        if ($paragraphKey === 0 && $wordKey === 0 && $symbolKey === 0) {
                            $currentBlockLineY = $symbolMidYPoint;
                            $symbolMeta->setIsFirstSymbolOfBlockLine(true);

                            $line = new Line();
                            $line->pushSymbol($symbolMeta);
                            $blockLine->addLine($line);
                        } else if ($symbolMidYPoint > ($currentBlockLineY + $yThreshold)) {
                            $symbolMeta->setIsFirstSymbolOfBlockLine(true);
                            $currentBlockLineY = $symbolMidYPoint;
                            $line = new Line();
                            $line->pushSymbol($symbolMeta);
                            $blockLine->addLine($line);
                        } else {
                            $symbolMeta->setIsFirstSymbolOfBlockLine(false);

                            $line->pushSymbol($symbolMeta);
                        }
                    }
                }
            }
        }

        // COMPOSE THE SENTENCES BY SYMBOLS AND SYMBOL'S METADATA PER BLOCK //
        foreach ($blockLine->getLines() as $lineKey=>$line) {

            $blockLineStartY = null;
            $blockLineEndY = null;
            $blockLineStartX = null;
            $blockLineEndX = null;

            $blockLineCompose = new BlockLineCompose();

            foreach($line->getContent() as $blockLineSymbol) {
                // Keep track of start and end of line coordinates
                /**
                 * @var Symbol $blockLineSymbol
                 */
                if (true === $blockLineSymbol->isFirstSymbolOfBlockLine() && null === $blockLineStartY) {
                    $blockLineStartY = $blockLineSymbol->getSymbolY();
                    $blockLineStartX = $blockLineSymbol->getSymbolX();
                }

                if (true === $blockLineSymbol->isLastSymbolOfBlockLine() && null === $blockLineEndY) {
                    $blockLineEndY = $blockLineSymbol->getSymbolY();
                    $blockLineEndX = $blockLineSymbol->getSymbolX();
                }

                if (true === $blockLineSymbol->isStartOfTheWord() && false === $blockLineSymbol->isFirstSymbolOfBlockLine()) {
                    $blockLineCompose->setDescription($blockLineCompose->getDescription() . " " . $blockLineSymbol->getText());
                } else {
                    $blockLineCompose->setDescription($blockLineCompose->getDescription() . $blockLineSymbol->getText());
                }
                $blockLineCompose->setBlockLineStartY($blockLineStartY);
                $blockLineCompose->setBlockLineEndY($blockLineEndY);
                $blockLineCompose->setBlockLineStartX($blockLineStartX);
                $blockLineCompose->setBlockLineEndX($blockLineEndX);

            }
            $blockLine->addLineComposed($blockLineCompose);
        }

        // COMPOSE FULL LINES AMONG BLOCKS BY LINES COMPOSED PREVIOUSLY PER BLOCK //
        $linesComposedTempBase = $blockLine->getLinesComposed();
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

                if (abs($blockA->getBlockLineStartY() - $blockB->getBlockLineEndY()) <= $thresholdIndicatorForSameLine) { // Means  that A is in same line with B
                    $lineY = ($blockA->getBlockLineStartY() + $blockB->getBlockLineEndY()) / 2;
                    $lineStartX = $blockB->getBlockLineStartX();
                    $lineEndX = $blockA->getBlockLineEndX();
                    $resultLineByKey = $blockLine->getResultLineByKeyOrNull($counter);

                    if ($blocksOrientation === ReceiptParserUtility::BLOCK_ORIENTATION_ZERO_DEG) {
                        if ($blockA->getBlockLineStartX() > $blockB->getBlockLineStartX()) {
                            if (null !== $resultLineByKey) {
                                $description = $blockB->getDescription() . " " . $resultLineByKey->getText();
                            } else {
                                $description = $blockB->getDescription() . " " . $blockA->getDescription();
                            }
                        } else {
                            if (null !== $resultLineByKey) {
                                $description = $resultLineByKey->getText() . " " . $blockB->getDescription();
                            } else {
                                $description = $blockA->getDescription() . " " . $blockB->getDescription();
                            }
                        }
                    } else {
                        if ($blockA->getBlockLineStartX() > $blockB->getBlockLineStartX()) {
                            if (null !== $resultLineByKey) {
                                $description = $resultLineByKey->getText() . " " . $blockB->getDescription();
                            } else {
                                $description = $blockB->getDescription() . " " . $blockA->getDescription();
                            }
                        } else {
                            if (null !== $resultLineByKey) {
                                $description = $blockB->getDescription() . " " . $resultLineByKey->getText();
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

                    $blockLine->addResultLine($resultLine);
                }
            }
            $counter++;
        }

        foreach ($linesComposedTempBase as $blockA) {
            $lineY = ($blockA->getBlockLineStartY() + $blockA->getBlockLineEndY()) / 2;
            $lineStartX = $blockA->getBlockLineStartX();
            $lineEndX = $blockA->getBlockLineEndX();
            $description = $blockA->getDescription();

            $resultLine = new ResultLine();
            $resultLine->setText($description);
            $resultLine->setLineEndX($lineEndX);
            $resultLine->setLineStartX($lineStartX);
            $resultLine->setLineY($lineY);

            $blockLine->addResultLine($resultLine);
        }

        // Order by line Y coordinates
        $mergedLines = [];

        foreach ($blockLine->getResultLines() as $resultLine) {
            /**
             * @var ResultLine $resultLine
             */
            $mergedLines['text'] = $resultLine->getText();
            $mergedLines['lineY'] = $resultLine->getLineY();
            $mergedLines['lineStartX'] = $resultLine->getLineStartX();
            $mergedLines['lineEndX'] = $resultLine->getLineEndX();
        }

        $linesYCoordinate = array_column($mergedLines, 'lineY');
        array_multisort($linesYCoordinate, SORT_ASC, $mergedLines);

        // Reset the result lines after order completion
        $blockLine->setResultLines($mergedLines);

        echo '<pre>';
        foreach ($blockLine->getResultLines() as $resultLine) {
            echo $resultLine->getText() . "\n";
        }
        echo '</pre>';
    }
}
