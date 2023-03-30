<?php

namespace Theod\CloudVisionClient\Processor;

use Illuminate\Http\Client\Response;
use Theod\CloudVisionClient\Builder\BlockLineBuilder;
use Theod\CloudVisionClient\Builder\Line;
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
        $symbolsMetaData = [];
        $composeBlockLineDescription = [];
        $thresholdIndicatorForSameLine = 30;
        $mergedLines = [];
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
                        $symbolMeta->setStartOfTheWord( 0 === $symbolKey);
                        $symbolMeta->setSymbolY($symbolMidYPoint);
                        $symbolMeta->setSymbolX($symbolMidXPoint);
                        $symbolMeta->setIsLastSymbolOfBlockLine(true);

                        if ($paragraphKey === 0 && $wordKey === 0 && $symbolKey === 0) {
                            $currentBlockLineY = $symbolMidYPoint;
                            $symbolMeta->setIsFirstSymbolOfBlockLine(true);

                            $line = new Line();
                            $line->pushSymbol($symbolMeta);
                            $blockLine->addLine($line);
//                            $blockLine->addSymbolToNewLine($line, $symbol, $symbolKey, $symbolMidYPoint, $symbolMidXPoint);
//                            $symbolsMetaData[$blockKey][$line][] = ["text" => $symbol['text'], "startOfTheWord" => $symbolKey === 0, "symbolY" => $symbolMidYPoint, "symbolX" => $symbolMidXPoint, "isFirstSymbolOfBlockLine" => true, "isLastSymbolOfBlockLine" => true];
                        } else if ($symbolMidYPoint > ($currentBlockLineY + $yThreshold)) {
                            $symbolMeta->setIsFirstSymbolOfBlockLine(true);
                            $currentBlockLineY = $symbolMidYPoint;
                            $line = new Line();
                            $line->pushSymbol($symbolMeta);
                            $blockLine->addLine($line);
//                            $blockLine->addSymbolToNewLine($line, $symbol, $symbolKey, $symbolMidYPoint, $symbolMidXPoint);
//                            $line++;
//                            $symbolsMetaData[$blockKey][$line][] = ["text" => $symbol['text'], "startOfTheWord" => $symbolKey === 0, "symbolY" => $symbolMidYPoint, "symbolX" => $symbolMidXPoint, "isFirstSymbolOfBlockLine" => true, "isLastSymbolOfBlockLine" => true];
                        } else {
                            $symbolMeta->setIsFirstSymbolOfBlockLine(false);

                            $line->pushSymbol($symbolMeta);
//                            $blockLine->addSymbolToExistingLine($line, $symbol, $symbolKey, $symbolMidYPoint, $symbolMidXPoint);
//                            $symbolsMetaData[$blockKey][$line][count($symbolsMetaData[$blockKey][$line]) - 1]["isLastSymbolOfBlockLine"] = false;
//                            $symbolsMetaData[$blockKey][$line][] = ["text" => $symbol['text'], "startOfTheWord" => $symbolKey === 0, "symbolY" => $symbolMidYPoint, "symbolX" => $symbolMidXPoint, "isFirstSymbolOfBlockLine" => false, "isLastSymbolOfBlockLine" => true];
                        }

                    }
                }
            }
# TODO CONTINUE REFACTORING
            // COMPOSE THE SENTENCES BY SYMBOLS AND SYMBOL'S METADATA PER BLOCK //
//            $line = 0;

//            foreach ($symbolsMetaData[$blockKey] as $line=>$blockLines) {
            foreach ($blockLine->getLines() as $lineKey=>$line) {
                $blockLineStartY = null;
                $blockLineEndY = null;
                $blockLineStartX = null;
                $blockLineEndX = null;

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
//                $composeBlockDescription[$blockKey][$line] = isset($composeBlockDescription[$blockKey][$line]) ? $composeBlockDescription[$blockKey][$line] . $blockLineSymbol["text"] : $blockLineSymbol["text"];

                    if (isset($composeBlockLineDescription[$blockKey][$lineKey])) {
                        if (true === $blockLineSymbol->isStartOfTheWord() && false === $blockLineSymbol->isFirstSymbolOfBlockLine()) {
                            $composeBlockLineDescription[$blockKey][$lineKey] = ['description' => $composeBlockLineDescription[$blockKey][$lineKey]['description'] . " " . $blockLineSymbol->getText(), 'blockLineStartY' => $blockLineStartY, "blockLineEndY" => $blockLineEndY, "blockLineStartX" => $blockLineStartX, "blockLineEndX" =>  $blockLineEndX];
                        } else {
                            $composeBlockLineDescription[$blockKey][$lineKey] = ['description' => $composeBlockLineDescription[$blockKey][$lineKey]['description'] . $blockLineSymbol->getText(), 'blockLineStartY' => $blockLineStartY, "blockLineEndY" => $blockLineEndY, "blockLineStartX" => $blockLineStartX, "blockLineEndX" =>  $blockLineEndX];
                        }
                    } else {
                        $composeBlockLineDescription[$blockKey][$lineKey] = ['description' => $blockLineSymbol->getText(), 'blockLineStartY' => $blockLineStartY, "blockLineEndY" => $blockLineEndY, "blockLineStartX" => $blockLineStartX, "blockLineEndX" =>  $blockLineEndX];
                    }
                }
            }
        }

// COMPOSE FULL LINES AMONG BLOCKS BY LINES COMPOSED PREVIOUSLY PER BLOCK //
        $leftOvers = [];
        $notFound = true;
        foreach ($composeBlockLineDescription as $blockKeyA=>$blockA) {
            foreach ($blockA as $lineDataAKey=>$lineDataA) {
                $counter = 0;
                $lineMatchFound = false;

                while ($counter < count($composeBlockLineDescription)) {
                    if ($blockKeyA === $counter) {
                        $counter++;
                        continue;
                    }

                    foreach ($composeBlockLineDescription[$counter] as $lineDataBKey => $lineDataB) {
                        if (abs($lineDataA["blockLineStartY"] - $lineDataB["blockLineEndY"]) <= $thresholdIndicatorForSameLine) { // Means  that A is in same line with B
                            if ($blocksOrientation === '0d') {
                                if ($lineDataA["blockLineStartX"] > $lineDataB["blockLineStartX"]) {
                                    $lineMatchFound = true;
                                    $notFound = false;
                                    $mergedLines[] = ['text' => $lineDataB["description"] . " " . $lineDataA["description"], 'lineY' => ($lineDataA["blockLineStartY"] + $lineDataB["blockLineEndY"]) / 2, 'lineStartX' => $lineDataB["blockLineStartX"], 'lineEndX' => $lineDataA["blockLineEndX"]];
                                    unset($composeBlockLineDescription[$counter][$lineDataBKey]);
                                    unset($composeBlockLineDescription[$blockKeyA][$lineDataAKey]);
                                } else {
                                    $lineMatchFound = true;
                                    $notFound = false;
                                    $mergedLines[] = ['text' => $lineDataA["description"] . " " . $lineDataB["description"], 'lineY' => ($lineDataA["blockLineStartY"] + $lineDataB["blockLineEndY"]) / 2, 'lineStartX' => $lineDataA["blockLineStartX"], 'lineEndX' => $lineDataB["blockLineEndX"]];
                                    unset($composeBlockLineDescription[$counter][$lineDataBKey]);
                                    unset($composeBlockLineDescription[$blockKeyA][$lineDataAKey]);
                                }
                            } else {
                                if ($lineDataA["blockLineStartX"] > $lineDataB["blockLineStartX"]) {
                                    $lineMatchFound = true;
                                    $notFound = false;
                                    $mergedLines[] = ['text' =>  $lineDataA["description"] . " " . $lineDataB["description"], 'lineY' => ($lineDataA["blockLineStartY"] + $lineDataB["blockLineEndY"]) / 2, 'lineStartX' => $lineDataB["blockLineStartX"], 'lineEndX' => $lineDataA["blockLineEndX"]];
                                    unset($composeBlockLineDescription[$counter][$lineDataBKey]);
                                    unset($composeBlockLineDescription[$blockKeyA][$lineDataAKey]);
                                } else {
                                    $lineMatchFound = true;
                                    $notFound = false;
                                    $mergedLines[] = ['text' => $lineDataB["description"] . " " . $lineDataA["description"], 'lineY' => ($lineDataA["blockLineStartY"] + $lineDataB["blockLineEndY"]) / 2, 'lineStartX' => $lineDataA["blockLineStartX"], 'lineEndX' => $lineDataB["blockLineEndX"]];
                                    unset($composeBlockLineDescription[$counter][$lineDataBKey]);
                                    unset($composeBlockLineDescription[$blockKeyA][$lineDataAKey]);
                                }
                            }
                        }
                    }
                    $counter++;
                }
            }
        }

        foreach ($composeBlockLineDescription as $blockKeyA=>$blockA) {
            if (count($blockA) > 0) {
                foreach ($blockA as $lineDataAKey=>$lineDataA) {
                    $mergedLines[] = ['text' => $lineDataA["description"], 'lineY' => ($lineDataA["blockLineStartY"] + $lineDataA["blockLineEndY"]) / 2, 'lineStartX' => $lineDataA["blockLineStartX"], 'lineEndX' => $lineDataA["blockLineEndX"]];
                }
            }
        }

// Order by line Y coordinates
        $linesYCoordinate = array_column($mergedLines, 'lineY');
        array_multisort($linesYCoordinate, SORT_ASC, $mergedLines);

        echo '<pre>';
        foreach ($mergedLines as $mergedLine) {
            echo $mergedLine['text'] . "\n";
        }
        echo '</pre>';

        $end_time = microtime(true);
// Calculating the script execution time
//        $execution_time = $end_time - $start_time;

//        echo "\n\n Execution time of script = " . $execution_time . " sec";
    }
}