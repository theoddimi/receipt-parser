<?php

namespace Theod\CloudVisionClient\Parser;

use Illuminate\Http\Client\Response;

class ReceiptParserResponse extends ParserResponse
{
    private string $blocksOrientation;

    /**
     * @param Response $response
     * @return ReceiptParserResponse
     */
    public static function createFromHttpResponse(Response $response): self
    {
        return new self($response);
    }

    /**
     * @return string
     */
    public function getBlocksOrientation(): string
    {
        return $this->blocksOrientation;
    }

    /**
     * @param string $blocksOrientation
     */
    public function setBlocksOrientation(string $blocksOrientation): void
    {
        $this->blocksOrientation = $blocksOrientation;
    }
}