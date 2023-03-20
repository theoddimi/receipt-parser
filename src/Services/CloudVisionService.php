<?php

namespace Theod\ReceiptParser\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class CloudVisionService
{
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
    public function __construct(private readonly PendingRequest $client){}

    public function postData(string $pathToReceipt = ''):  Response
    {
        return $this->client->withBody(self::REQUEST_BODY)->post('images:annotate/{key}');
    }

}