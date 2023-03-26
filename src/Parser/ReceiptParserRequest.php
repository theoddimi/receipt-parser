<?php

namespace Theod\CloudVisionClient\Parser;

use Theod\CloudVisionClient\Objects\Uri;

class ReceiptParserRequest extends ParserRequest
{
    /**
     * @var array
     */
    private array $body;

    public function __construct()
    {
        $this->body['requests'] = [
            [
                'image' => [
                    'source' => ['imageUri' => '']
                ],
                'features' => [
                    [
                        'type' => 'TEXT_DETECTION',
                        'model' => 'builtin/weekly'
                    ]
                ],
                'imageContext' => [
                    'cropHintsParams' => [
                        'aspectRatios' => []
                    ],
                    'languageHints' => ['el', 'en'],
                    'textDetectionParams' => [
                        'enableTextDetectionConfidenceScore' => true,
                        'advancedOcrOptions' => ['legacy_layout']
                    ],
                    'latLongRect' => [
                        'minLatLng' => [
                            "latitude" => 37.4219999,
                            "longitude" => -122.0840575
                        ],
                        'maxLatLng' => [
                            "latitude" => 37.4226111,
                            "longitude" => -122.0827935
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param Uri $uri
     * @return ReceiptParserRequest
     */
    public function addSourceUriToBody(Uri $uri): self
    {
        $this->body['requests'][0]['image']['source']['imageUri'] = $uri->getValue();

        return $this;
    }

    /**
     * @return string
     */
    public function getBodyAsJson(): string
    {
        return json_encode($this->body);
    }

    /**
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }

//    public static function createBodyRequest(string $jsonResponse)
//    {
//        $receiptParser = new self();
//        $receiptParser->jsonBody = $jsonResponse;
//    }
}