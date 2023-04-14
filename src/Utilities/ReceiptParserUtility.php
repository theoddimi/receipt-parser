<?php

namespace Theod\CloudVisionClient\Utilities;

use Theod\CloudVisionClient\Builder\ReceiptParserBuilder;
use Theod\CloudVisionClient\Builder\ResultLine;
use Theod\CloudVisionClient\Builder\SymbolBound;
use Theod\CloudVisionClient\Parser\ReceiptParserResponse;
use Theod\CloudVisionClient\Collections\ReceiptParser\ResultLineCollection;

class ReceiptParserUtility
{
    public const BLOCK_ORIENTATION_ZERO_DEG = '0d';
    public const BLOCK_ORIENTATION_NINETY_DEG = '90d';

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
        return $paragraph['words'];
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

    /**
     * @param int $paragraphKey
     * @param int $wordKey
     * @param int $symbolKey
     * @return bool
     */
    public function isFirstElementOfTheBlock(int $paragraphKey, int $wordKey, int $symbolKey): bool
    {
        return $paragraphKey === 0 && $wordKey === 0 && $symbolKey === 0;
    }

    /**
     * @param float $symbolMidYPoint
     * @param float $currentBlockLineY
     * @param float $yThreshold
     * @return bool
     */
    public function assumeNewBlockLine(float $symbolMidYPoint, float $currentBlockLineY, float $yThreshold): bool
    {
        return $symbolMidYPoint > ($currentBlockLineY + $yThreshold);
    }

    /**
     * @param array $symbol
     * @param string $orientation
     * @return SymbolBound
     */
    public function getBoundsForSymbolByOrientation(array $symbol, string $orientation): SymbolBound
    {
        $symbolBound = new SymbolBound();

        if (self::BLOCK_ORIENTATION_ZERO_DEG === $orientation) {
            $symbolBound->setSymbolTopYBoundAvg(
                $this->calculateTopSideYBoundAverageForSymbolAndOrientationZero($symbol)
            );
            $symbolBound->setSymbolBottomYBoundAvg(
                $this->calculateBottomSideYBoundAverageForSymbolAndOrientationZero($symbol)
            );
            $symbolBound->setSymbolLeftXBoundAvg(
                $this->calculateLeftSideXBoundAverageForSymbolAndOrientationZero($symbol)
            );
            $symbolBound->setSymbolRightXBoundAvg(
                $this->calculateRightSideXBoundAverageForSymbolAndOrientationZero($symbol)
            );
        } else {
            $symbolBound->setSymbolTopYBoundAvg(
                $this->calculateTopSideYBoundAverageForSymbolAndOrientationNinety($symbol)
            );
            $symbolBound->setSymbolBottomYBoundAvg(
                $this->calculateBottomSideYBoundAverageForSymbolAndOrientationNinety($symbol)
            );
            $symbolBound->setSymbolLeftXBoundAvg(
                $this->calculateLeftSideXBoundAverageForSymbolAndOrientationNinety($symbol)
            );
            $symbolBound->setSymbolRightXBoundAvg(
                $this->calculateRightSideXBoundAverageForSymbolAndOrientationNinety($symbol)
            );
        }

        return $symbolBound;
    }

    /**
     * @param SymbolBound $symbolBounds
     * @return float
     */
    public function getMiddlePointOfYCoordinateFromSymbolBounds(SymbolBound $symbolBounds): float
    {
         return $this->calculateMiddleYPointForSymbolBoundsY(
             $symbolBounds->getSymbolTopYBoundAvg(),
             $symbolBounds->getSymbolBottomYBoundAvg(),
         );
    }

    /**
     * @param SymbolBound $symbolBounds
     * @return float
     */
    public function getMiddlePointOfXCoordinateFromSymbolBounds(SymbolBound $symbolBounds): float
    {
        return $this->calculateMiddleXPointForSymbolBoundsX(
            $symbolBounds->getSymbolLeftXBoundAvg(),
            $symbolBounds->getSymbolRightXBoundAvg()
        );
    }

    /**
     * @param ReceiptParserBuilder $receiptParserBuilder
     * @return ResultLineCollection
     */
    public function orderLinesOfReceiptParserBuilderResults(ReceiptParserBuilder $receiptParserBuilder): ResultLineCollection
    {
        $resultLineCollection = new ResultLineCollection();

        $results = $receiptParserBuilder->getResultLines()->toArray();

        $linesYCoordinate = array_column($results, 'lineY');
        array_multisort($linesYCoordinate, SORT_ASC, $results);

        foreach ($results as $item) {
            $resultLine = new ResultLine();
            $resultLine->setText($item['text']);
            $resultLine->setLineEndX($item['lineEndX']);
            $resultLine->setLineStartX($item['lineStartX']);
            $resultLine->setLineY($item['lineY']);

            $resultLineCollection->addItem($resultLine);
        }

        return $resultLineCollection;
    }
}