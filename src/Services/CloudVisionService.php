<?php

namespace Theod\CloudVisionClient\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Theod\CloudVisionClient\Parser\ReceiptParserRequest;
use Exception;
use Theod\CloudVisionClient\Parser\ReceiptParserResponse;

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

    /**
     * @throws Exception
     */
    public function postImageAnnotateWithRequest(ReceiptParserRequest $receiptParserRequest):  ReceiptParserResponse
    {
        $httpResponse =  $this->client->withBody($receiptParserRequest->getBodyAsJson())->send('post','images:annotate?key={key}');

        return ReceiptParserResponse::createFromHttpResponse($httpResponse);
    }
}
