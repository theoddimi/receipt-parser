<?php

namespace Theod\CloudVisionClient\Processor;

use Theod\CloudVisionClient\Builder\ReceiptParserBuilder;
use Theod\CloudVisionClient\Parser\ReceiptParserRequest;
use Theod\CloudVisionClient\Utilities\ReceiptParserUtility;
use Theod\CloudVisionClient\Processor\Contracts\ReceiptParserProcessorInterface;
use Theod\CloudVisionClient\Services\CloudVisionService;
use Exception;

class ReceiptParserProcessor extends Processor implements ReceiptParserProcessorInterface
{
    public function __construct(
        private readonly CloudVisionService $cloudVisionService,
        private readonly ReceiptParserUtility $receiptParserUtility
    ){}

    /**
     * @throws Exception
     */
    public function run()
    {
        // Init variables
        $yThreshold = 30;
        $currentBlockLineY = -1;

        $this->start();

        $receiptParserRequest = new ReceiptParserRequest();
        $receiptParserRequest->addSourceUriToBody($this->getSourceUriToProcess());

        $receiptParserResponse = $this->cloudVisionService->postImageAnnotateWithRequest($receiptParserRequest);

        // Find orientation of blocks returned
        $blocksOrientation = $this->receiptParserUtility->specifyBlocksOrientationFromResponse($receiptParserResponse);
        $receiptParserResponse->setBlocksOrientation($blocksOrientation);
        
        $response = $receiptParserResponse->toArray();
        $blocks = $this->receiptParserUtility->retrieveBlocksFromDecodedResponse($response);

        $builder = ReceiptParserBuilder::init($this->receiptParserUtility, $blocksOrientation);
        // Start blocks looping
        $builder = $builder->buildLineGroupsOfSymbolsFromBlocks(
            $blocks,
            $blocksOrientation,
            $yThreshold,
            $currentBlockLineY
        );

        // Compose the sentences from line groups of symbols per block
        $builder = $builder->buildBlockFullLinesFromLineGroupsOfSymbols();

        // Compose sentences among blocks combining full lines of each block
        $builder = $builder->buildResultLinesFromBlockFullLines();

        // Order by line, based on Y coordinates
        $orderedResults = $this->receiptParserUtility->orderLinesOfReceiptParserBuilderResults($builder);

        // Reset the result lines after order completion
        $builder->setResultLines($orderedResults);

        echo '<pre>';
        foreach ($builder->getResultLines() as $resultLine) {
            echo $resultLine->getText() . "\n";
        }
        echo '</pre>';
    }
}
