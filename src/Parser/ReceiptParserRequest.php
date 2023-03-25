<?php

namespace Theod\CloudVisionClient\Parser;

use Theod\CloudVisionClient\Objects\Uri;

class ReceiptParserRequest extends ParserRequest
{
    /**
     * @var array
     */
    private array $body;

    private const REQUEST_BODY = '{
  "requests": [
    {
      "image": {
          "source": {
              "imageUri": "gs://gainz-expensea-train/image1-0.jpg"
          }
      },
      "features": [
        {
          "type": "DOCUMENT_TEXT_DETECTION",
          "model":"builtin/weekly"
        },
      ],
      "imageContext": {
        "cropHintsParams": {
            "aspectRatios": [
             0.8,
            1,
            1.2
            ]
        },
        "languageHints": [
            "el", "en"
        ],
        "textDetectionParams": {
            "enableTextDetectionConfidenceScore": true,
            "advancedOcrOptions": [
                "legacy_layout"                    
            ],
        },
      }
    }
  ]
}';

    public function __construct()
    {
        $this->body['requests'] = [
            [
                'image' => [
                    'source' => ['imageUri' => '']
                ],
                'features' => [
                    [
                        'type' => 'DOCUMENT_TEXT_DETECTION',
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
        $this->body['requests']['image']['source']['imageUri'] = $uri->getValue();

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