<?php

namespace Theod\ReceiptParser\Processor;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Theod\ReceiptParser\Services\CloudVisionService;

class ReceiptParserProcessor extends Processor
{
    private const CONTENT_TYPE = 'application/json';

    private Response $cloudVisionOcrResponse;

    public function __construct(
        private readonly CloudVisionService $cloudVisionService
    ){}

    public function run()
    {
        $response = $this->cloudVisionService->postData();

        $responseJson = $response->json();
        $start_time = microtime(true);
// Init variables
        $currentBlockLineY = -1;
        $currentBlockLineX = -1;
        $yThreshold = 10;
        $symbolsMetaData = [];
        $composeBlockLineDescription = [];
        $thresholdIndicatorForSameLine = 20;
        $mergedLines = [];


// Find orientation of blocks returned
// Blocks
        $firstBlockTextAnnotation = $responseJson["responses"][0]["textAnnotations"][1];
        $boundingPolyVertices = $firstBlockTextAnnotation["boundingPoly"]['vertices'];
        $firstWordWidth = $boundingPolyVertices[0]["x"] + $boundingPolyVertices[1]["x"] + $boundingPolyVertices[2]["x"] + $boundingPolyVertices[3]["x"];
        $firstWordHeight = $boundingPolyVertices[0]["y"] + $boundingPolyVertices[1]["y"] + $boundingPolyVertices[2]["y"] + $boundingPolyVertices[3]["y"];
        $orientation = $firstWordWidth > $firstWordHeight === true ? '0d' : '90d';


// Blocks
        $blocks = $responseJson["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"];

// First line coordinates. Same as the first letter of firstblock found from the scanned document.
        $firstSymbolBounds = $blocks[0]['paragraphs'][0]['words'][0]['symbols'][0]['boundingBox']['vertices'];


// Start blocks looping
        foreach ($blocks as $blockKey=>$block) {

            $line = 0;
            //Paragraphs
            $paragraphs = $block['paragraphs'];

            foreach ($paragraphs as $paragraphKey=>$paragraph) {
                // Words
                $words = $paragraph['words'];

                foreach ($words as $wordKey=>$word) {
                    // Symbols
                    $symbols = $word["symbols"];

                    // First symbol of word of the block paragraph (assumed that a block has only one paragraph for now)
                    $firstSymbolOfTheWord = $symbols[0]; // DO WEE NEED THIS?
                    // Last symbol of the word
                    $lastSymbolOfTheWord = $symbols[count($symbols)-1]; // AND THIS?

                    foreach ($symbols as $symbolKey=>$symbol) {
                        ################ Calculate current symbol's y and check line status #########################
                        // Calculate the average of symbols' left and right boundaries y coordinates for top and bottom side
                        if ($orientation === '0d') {
                            $symbolTopYBoundAvg = ($symbol['boundingBox']['vertices'][0]['y'] + $symbol['boundingBox']['vertices'][1]['y']) / 2;
                            $symbolBottomYBoundAvg = ($symbol['boundingBox']['vertices'][2]['y'] + $symbol['boundingBox']['vertices'][3]['y']) / 2;

                            // Calculate the point in the middle of the top and bottom Y coordinates of the symbol
                            $symbolMidYPoint = ($symbolTopYBoundAvg + $symbolBottomYBoundAvg) / 2;
                        } else {
                            $symbolTopYBoundAvg = ($symbol['boundingBox']['vertices'][0]['x'] + $symbol['boundingBox']['vertices'][1]['x']) / 2;
                            $symbolBottomYBoundAvg = ($symbol['boundingBox']['vertices'][2]['x'] + $symbol['boundingBox']['vertices'][3]['x']) / 2;

                            // Calculate the point in the middle of the top and bottom Y coordinates of the symbol
                            $symbolMidYPoint = ($symbolTopYBoundAvg + $symbolBottomYBoundAvg) / 2;
                        }
                        ############################################################################################

                        ################ Calculate current symbol x and check line status #########################
                        // Calculate the average of symbols' left and right boundaries y coordinates for top and bottom side
                        if ($orientation === '0d') {
                            $symbolLeftXBoundAvg = ($symbol['boundingBox']['vertices'][0]['x'] + $symbol['boundingBox']['vertices'][3]['x']) / 2;
                            $symbolRightXBoundAvg = ($symbol['boundingBox']['vertices'][1]['x'] + $symbol['boundingBox']['vertices'][2]['x']) / 2;

                            // Calculate the point in the middle of the left and right X coordinates of the symbol
                            $symbolMidXPoint = ($symbolLeftXBoundAvg + $symbolRightXBoundAvg) / 2;
                        } else {
                            $symbolLeftXBoundAvg = ($symbol['boundingBox']['vertices'][0]['y'] + $symbol['boundingBox']['vertices'][3]['y']) / 2;
                            $symbolRightXBoundAvg = ($symbol['boundingBox']['vertices'][1]['y'] + $symbol['boundingBox']['vertices'][2]['y']) / 2;

                            // Calculate the point in the middle of the left and right X coordinates of the symbol
                            $symbolMidXPoint = ($symbolLeftXBoundAvg + $symbolRightXBoundAvg) / 2;
                        }
                        ############################################################################################

                        ################## Specify the line #############
                        if ($paragraphKey === 0 && $wordKey === 0 && $symbolKey === 0) {
                            $currentBlockLineY = $symbolMidYPoint;
                            $symbolsMetaData[$blockKey][$line][] = ["text" => $symbol['text'], "startOfTheWord" => true, "symbolY" => $symbolMidYPoint, "symbolX" => $symbolMidXPoint, "isFirstSymbolOfBlockLine" => true, "isLastSymbolOfBlockLine" => true];
                        } else if ($symbolMidYPoint > ($currentBlockLineY + $yThreshold)) {
                            $line++;
                            $currentBlockLineY = $symbolMidYPoint;
                            $symbolsMetaData[$blockKey][$line][] = ["text" => $symbol['text'], "startOfTheWord" => $symbolKey === 0, "symbolY" => $symbolMidYPoint, "symbolX" => $symbolMidXPoint, "isFirstSymbolOfBlockLine" => true, "isLastSymbolOfBlockLine" => true];
                        } else {
                            $symbolsMetaData[$blockKey][$line][count($symbolsMetaData[$blockKey][$line]) - 1]["isLastSymbolOfBlockLine"] = false;
                            $symbolsMetaData[$blockKey][$line][] = ["text" => $symbol['text'], "startOfTheWord" => $symbolKey === 0, "symbolY" => $symbolMidYPoint, "symbolX" => $symbolMidXPoint, "isFirstSymbolOfBlockLine" => false, "isLastSymbolOfBlockLine" => true];
                        }
                    }
                }
            }

            // COMPOSE THE SENTENCES BY SYMBOLS AND SYMBOL'S METADATA PER BLOCK //
            $line = 0;
            foreach ($symbolsMetaData[$blockKey] as $line=>$blockLines) {
                $blockLineStartY = null;
                $blockLineEndY = null;
                $blockLineStartX = null;
                $blockLineEndX = null;

                foreach($blockLines as $blockLineSymbol) {
                    // Keep track of start and end of line coordinates
                    if (true === $blockLineSymbol['isFirstSymbolOfBlockLine'] && null === $blockLineStartY) {
                        $blockLineStartY = $blockLineSymbol["symbolY"];
                        $blockLineStartX = $blockLineSymbol["symbolX"];
                    }

                    if (true === $blockLineSymbol['isLastSymbolOfBlockLine'] && null === $blockLineEndY) {
                        $blockLineEndY = $blockLineSymbol["symbolY"];
                        $blockLineEndX = $blockLineSymbol["symbolX"];
                    }
//                $composeBlockDescription[$blockKey][$line] = isset($composeBlockDescription[$blockKey][$line]) ? $composeBlockDescription[$blockKey][$line] . $blockLineSymbol["text"] : $blockLineSymbol["text"];

                    if (isset($composeBlockLineDescription[$blockKey][$line])) {
                        if (true === $blockLineSymbol['startOfTheWord'] && false === $blockLineSymbol['isFirstSymbolOfBlockLine']) {
                            $composeBlockLineDescription[$blockKey][$line] = ['description' => $composeBlockLineDescription[$blockKey][$line]['description'] . " " . $blockLineSymbol["text"], 'blockLineStartY' => $blockLineStartY, "blockLineEndY" => $blockLineEndY, "blockLineStartX" => $blockLineStartX, "blockLineEndX" =>  $blockLineEndX];
                        } else {
                            $composeBlockLineDescription[$blockKey][$line] = ['description' => $composeBlockLineDescription[$blockKey][$line]['description'] . $blockLineSymbol["text"], 'blockLineStartY' => $blockLineStartY, "blockLineEndY" => $blockLineEndY, "blockLineStartX" => $blockLineStartX, "blockLineEndX" =>  $blockLineEndX];
                        }
                    } else {
                        $composeBlockLineDescription[$blockKey][$line] = ['description' => $blockLineSymbol["text"], 'blockLineStartY' => $blockLineStartY, "blockLineEndY" => $blockLineEndY, "blockLineStartX" => $blockLineStartX, "blockLineEndX" =>  $blockLineEndX];
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
                            if ($orientation === '0d') {
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


        foreach ($mergedLines as $mergedLine) {
            echo $mergedLine['text'] . "\n";
        }


        $end_time = microtime(true);
// Calculating the script execution time
        $execution_time = $end_time - $start_time;

        echo "\n\n Execution time of script = " . $execution_time . " sec";
    }
}