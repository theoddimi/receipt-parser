<?php

namespace Theod\CloudVisionClient\Utilities;

use Theod\CloudVisionClient\Builder\Line;
use Theod\CloudVisionClient\Builder\ReceiptParserBuilder;
use Theod\CloudVisionClient\Builder\Symbol;
use Theod\CloudVisionClient\Parser\ReceiptParserResponse;

class ReceiptParserUtility
{
    public const BLOCK_ORIENTATION_ZERO_DEG = '0d';
    public const BLOCK_ORIENTATION_NINETY_DEG = '90d';

    /**
     * @param array $blocks
     * @param string $blocksOrientation
     * @param float $yThreshold
     * @return ReceiptParserBuilder
     */
    public function createLineGroupsOfSymbolsFromBlocks(
        array $blocks,
        string $blocksOrientation,
        float $yThreshold
    ): ReceiptParserBuilder {
        $builder = new ReceiptParserBuilder();

        foreach ($blocks as $block) {
            $builder->setBlock($block);
            $paragraphs = $this->getParagraphsFromBlock($block);

            foreach ($paragraphs as $paragraphKey => $paragraph) {
                $words = $this->getWordsFromParagraph($paragraph);

                foreach ($words as $wordKey => $word) {
                    $symbols = $this->getSymbolsFromWord($word);

                    foreach ($symbols as $symbolKey => $symbol) {
                        ################ Calculate current symbol's y and check line status #########################
                        // Calculate the average of symbols' left and right boundaries y coordinates for top and bottom side
                        if (ReceiptParserUtility::BLOCK_ORIENTATION_ZERO_DEG === $blocksOrientation) {
                            $symbolTopYBoundAvg = $this->calculateTopSideYBoundAverageForSymbolAndOrientationZero($symbol);
                            $symbolBottomYBoundAvg = $this->calculateBottomSideYBoundAverageForSymbolAndOrientationZero($symbol);
                            $symbolLeftXBoundAvg = $this->calculateLeftSideXBoundAverageForSymbolAndOrientationZero($symbol);
                            $symbolRightXBoundAvg = $this->calculateRightSideXBoundAverageForSymbolAndOrientationZero($symbol);
                        } else {
                            $symbolTopYBoundAvg = $this->calculateTopSideYBoundAverageForSymbolAndOrientationNinety($symbol);
                            $symbolBottomYBoundAvg = $this->calculateBottomSideYBoundAverageForSymbolAndOrientationNinety($symbol);
                            $symbolLeftXBoundAvg = $this->calculateLeftSideXBoundAverageForSymbolAndOrientationNinety($symbol);
                            $symbolRightXBoundAvg = $this->calculateRightSideXBoundAverageForSymbolAndOrientationNinety($symbol);
                        }

                        // Calculate the point in the middle of the top and bottom Y coordinates of the symbol
                        $symbolMidYPoint = $this->calculateMiddleYPointForSymbolBoundsY(
                            $symbolTopYBoundAvg,
                            $symbolBottomYBoundAvg
                        );

                        $symbolMidXPoint = $this->calculateMiddleXPointForSymbolBoundsX(
                            $symbolLeftXBoundAvg,
                            $symbolRightXBoundAvg
                        );

                        ################## Compose block line words, symbol by symbol #############
                        $line = new Line();

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
                            $builder->addLine($line);
                        } else if ($symbolMidYPoint > ($currentBlockLineY + $yThreshold)) {
                            $symbolMeta->setIsFirstSymbolOfBlockLine(true);
                            $currentBlockLineY = $symbolMidYPoint;
                            $line = new Line();
                            $line->pushSymbol($symbolMeta);
                            $builder->addLine($line);
                        } else {
                            $symbolMeta->setIsFirstSymbolOfBlockLine(false);
                            $line->pushSymbol($symbolMeta);
                        }
                    }
                }
            }
        }

        return $builder;
    }

    /**
     * @param array $json
     * @return array
     */
    public function getFirstBlockTextAnnotationFromJsonResponse(array $json): array
    {
        return  $json["responses"][0]["textAnnotations"][1];
    }

    /**
     * @param array $json
     * @return array
     */
    public function getTextAnnotationFirstBlockBoundingPolyVerticesFromJsonResponse(array $json): array
    {
        $textAnnotationFirstBlock = $this->getFirstBlockTextAnnotationFromJsonResponse($json);

        return $textAnnotationFirstBlock["boundingPoly"]['vertices'];
    }

    /**
     * @param array $boundingPolyVertices
     * @return float
     */
    public function calculateWordWidthFromBoundingPolyVertices(array $boundingPolyVertices): float
    {
        return $boundingPolyVertices[0]["x"] + $boundingPolyVertices[1]["x"] + $boundingPolyVertices[2]["x"] + $boundingPolyVertices[3]["x"];
    }

    /**
     * @param array $boundingPolyVertices
     * @return float
     */
    public function calculateWordHeightFromBoundingPolyVertices(array $boundingPolyVertices): float
    {
        return $boundingPolyVertices[0]["y"] + $boundingPolyVertices[1]["y"] + $boundingPolyVertices[2]["y"] + $boundingPolyVertices[3]["y"];
    }

    /**
     * @param float $wordWidth
     * @param float $wordHeight
     * @return string
     */
    public function specifyBlocksOrientationFromWordWidthAndHeight(float $wordWidth, float $wordHeight): string # TODO Return BlockOrientationObject
    {
        if ($wordWidth > $wordHeight === true) {
            return self::BLOCK_ORIENTATION_ZERO_DEG;
        }

        return self::BLOCK_ORIENTATION_NINETY_DEG;
    }

    /**
     * @param ReceiptParserResponse $response
     * @return string
     */
    public function specifyBlocksOrientationFromResponse(ReceiptParserResponse $response): string # TODO Return BlockOrientationObject
    {
        $textAnnotationFirstBlockBoundingPolyVertices = $this->getTextAnnotationFirstBlockBoundingPolyVerticesFromJsonResponse($response->toArray());
        $wordWidth = $this->calculateWordWidthFromBoundingPolyVertices($textAnnotationFirstBlockBoundingPolyVertices);
        $wordHeight = $this->calculateWordHeightFromBoundingPolyVertices($textAnnotationFirstBlockBoundingPolyVertices);

        return $this->specifyBlocksOrientationFromWordWidthAndHeight($wordWidth, $wordHeight);
    }

    /**
     * @param array $response
     * @return array
     */
    public function retrieveBlocksFromDecodedResponse(array $response): array
    {
        return $response["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"];
    }

    /**
     * @param array $block
     * @return array
     */
    public function getParagraphsFromBlock(array $block): array
    {
        return $block['paragraphs'];
    }

    /**
     * @param array $paragraph
     * @return array
     */
    public function getWordsFromParagraph(array $paragraph): array
    {
        return $paragraph['words'];;
    }

    /**
     * @param array $word
     * @return array
     */
    public function getSymbolsFromWord(array $word): array
    {
        return $word["symbols"];
    }

    /**
     * @param array $symbolMeta
     * @return float
     */
    public function calculateTopSideYBoundAverageForSymbolAndOrientationZero(array $symbolMeta): float
    {
        return ($symbolMeta['boundingBox']['vertices'][0]['y'] + $symbolMeta['boundingBox']['vertices'][1]['y']) / 2;
    }

    /**
     * @param array $symbolMeta
     * @return float
     */
    public function calculateBottomSideYBoundAverageForSymbolAndOrientationZero(array $symbolMeta): float
    {
        return ($symbolMeta['boundingBox']['vertices'][2]['y'] + $symbolMeta['boundingBox']['vertices'][3]['y']) / 2;
    }

    /**
     * @param array $symbolMeta
     * @return float
     */
    public function calculateTopSideYBoundAverageForSymbolAndOrientationNinety(array $symbolMeta): float
    {
        return ($symbolMeta['boundingBox']['vertices'][0]['x'] + $symbolMeta['boundingBox']['vertices'][1]['x']) / 2;
    }

    /**
     * @param array $symbolMeta
     * @return float
     */
    public function calculateBottomSideYBoundAverageForSymbolAndOrientationNinety(array $symbolMeta): float
    {
        return  ($symbolMeta['boundingBox']['vertices'][2]['x'] + $symbolMeta['boundingBox']['vertices'][3]['x']) / 2;
    }

    /**
     * @param array $symbolMeta
     * @return float
     */
    public function calculateLeftSideXBoundAverageForSymbolAndOrientationZero(array $symbolMeta): float
    {
        return  ($symbolMeta['boundingBox']['vertices'][0]['x'] + $symbolMeta['boundingBox']['vertices'][3]['x']) / 2;
    }

    /**
     * @param array $symbolMeta
     * @return float
     */
    public function calculateRightSideXBoundAverageForSymbolAndOrientationZero(array $symbolMeta): float
    {
        return  ($symbolMeta['boundingBox']['vertices'][1]['x'] + $symbolMeta['boundingBox']['vertices'][2]['x']) / 2;
    }

    /**
     * @param array $symbolMeta
     * @return float
     */
    public function calculateLeftSideXBoundAverageForSymbolAndOrientationNinety(array $symbolMeta): float
    {
        return ($symbolMeta['boundingBox']['vertices'][0]['y'] + $symbolMeta['boundingBox']['vertices'][3]['y']) / 2;
    }

    /**
     * @param array $symbolMeta
     * @return float
     */
    public function calculateRightSideXBoundAverageForSymbolAndOrientationNinety(array $symbolMeta): float
    {
        return ($symbolMeta['boundingBox']['vertices'][1]['y'] + $symbolMeta['boundingBox']['vertices'][2]['y']) / 2;
    }

    /**
     * @param float $symbolTopYBoundAvg
     * @param float $symbolBottomYBoundAvg
     * @return float
     */
    public function calculateMiddleYPointForSymbolBoundsY(float $symbolTopYBoundAvg, float $symbolBottomYBoundAvg): float
    {
        return ($symbolTopYBoundAvg + $symbolBottomYBoundAvg) / 2;
    }

    /**
     * @param float $symbolTopXBoundAvg
     * @param float $symbolBottomXBoundAvg
     * @return float
     */
    public function calculateMiddleXPointForSymbolBoundsX(float $symbolTopXBoundAvg, float $symbolBottomXBoundAvg): float
    {
        return ($symbolTopXBoundAvg + $symbolBottomXBoundAvg) / 2;
    }
}