<?php

namespace Theod\CloudVisionClient\Parser;

use Illuminate\Http\Client\Response;

abstract class ParserResponse
{
    private Response $httpResponse;

    public function __construct(Response $response)
    {
        $this->httpResponse = $response;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->httpResponse->json();
    }
}