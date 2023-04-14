<?php

namespace Theod\CloudVisionClient\Processor;

use Theod\CloudVisionClient\Builder\ReceiptParserBuilder;
use Theod\CloudVisionClient\Parser\ReceiptParserRequest;
use Theod\CloudVisionClient\Parser\ReceiptParserResponse;
use Theod\CloudVisionClient\Utilities\ReceiptParserUtility;
use Theod\CloudVisionClient\Processor\Contracts\ReceiptParserProcessorInterface;
use Theod\CloudVisionClient\Services\CloudVisionService;
use Exception;

class ReceiptParserProcessor extends Processor implements ReceiptParserProcessorInterface
{
    private const THRESHOLD_Y = 30;

    public function __construct(
        private readonly CloudVisionService $cloudVisionService,
        private readonly ReceiptParserUtility $receiptParserUtility
    ){}

    /**
     * @return ReceiptParserBuilder
     * @throws Exception
     */
    public function run(): ReceiptParserBuilder
    {
        $this->start();

        $receiptParserResponse = $this->processRequest();

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
            self::THRESHOLD_Y
        );

        // Compose the sentences from line groups of symbols per block
        $builder = $builder->buildBlockFullLinesFromLineGroupsOfSymbols();

        // Compose sentences among blocks combining full lines of each block
        $builder = $builder->buildResultLinesFromBlockFullLines();

        // Order by line, based on Y coordinates
        $orderedResults = $this->receiptParserUtility->orderLinesOfReceiptParserBuilderResults($builder);

        // Reset the result lines
        $builder->setResultLines($orderedResults);

        $this->endSuccess();

        return $builder;
    }

    /**
     * @return ReceiptParserResponse
     * @throws Exception
     */
    private function processRequest(): ReceiptParserResponse
    {
        $receiptParserRequest = new ReceiptParserRequest();
        $receiptParserRequest->addSourceUriToBody($this->getSourceUriToProcess());

        return $this->cloudVisionService->postImageAnnotateWithRequest($receiptParserRequest);
    }
}
