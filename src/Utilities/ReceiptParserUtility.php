<?php

namespace Theod\CloudVisionClient\Utilities;

class ReceiptParserUtility
{
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
            return '0d';
        }

        return '90d';
    }

    /**
     * @param array $json
     * @return string
     */
    public function specifyBlocksOrientationFromJsonResponse(array $json): string # TODO Return BlockOrientationObject
    {
        $textAnnotationFirstBlockBoundingPolyVertices = $this->getTextAnnotationFirstBlockBoundingPolyVerticesFromJsonResponse($json);
        $wordWidth = $this->calculateWordWidthFromBoundingPolyVertices($textAnnotationFirstBlockBoundingPolyVertices);
        $wordHeight = $this->calculateWordHeightFromBoundingPolyVertices($textAnnotationFirstBlockBoundingPolyVertices);

        return $this->specifyBlocksOrientationFromWordWidthAndHeight($wordWidth, $wordHeight);
    }
}