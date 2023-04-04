<?php

namespace Theod\CloudVisionClient\Processor;

use Illuminate\Http\Client\Response;
use Theod\CloudVisionClient\Builder\BlockLineCompose;
use Theod\CloudVisionClient\Builder\Line;
use Theod\CloudVisionClient\Builder\ReceiptParserBuilder;
use Theod\CloudVisionClient\Builder\ResultLine;
use Theod\CloudVisionClient\Builder\Symbol;
use Theod\CloudVisionClient\Parser\ReceiptParserRequest;
use Theod\CloudVisionClient\Parser\ReceiptParserResponse;
use Theod\CloudVisionClient\Utilities\ReceiptParserUtility;
use Theod\CloudVisionClient\Processor\Contracts\ReceiptParserProcessorInterface;
use Theod\CloudVisionClient\Services\CloudVisionService;

class ReceiptParserProcessor extends Processor implements ReceiptParserProcessorInterface
{
    private const CONTENT_TYPE = 'application/json';

    private Response $cloudVisionResponse;

    public function __construct(
        private readonly CloudVisionService $cloudVisionService,
        private readonly ReceiptParserUtility $receiptParserUtility
    ){}


    public function run()
    {
        // Init variables
        $yThreshold = 30;
        $thresholdIndicatorForSameLine = 30;
        $currentBlockLineY = -1;

        $this->start();

        $receiptParserRequest = new ReceiptParserRequest();
        $receiptParserRequest->addSourceUriToBody($this->getSourceUriToProcess());

        $httpResponse = $this->cloudVisionService->postImageAnnotateWithRequest($receiptParserRequest);
        $receiptParserResponse = ReceiptParserResponse::createFromHttpResponse($httpResponse);

        // Find orientation of blocks returned
        $blocksOrientation = $this->receiptParserUtility->specifyBlocksOrientationFromResponse($receiptParserResponse);
        $receiptParserResponse->setBlocksOrientation($blocksOrientation);
        
        $response = $receiptParserResponse->toArray();
        $blocks = $this->receiptParserUtility->retrieveBlocksFromDecodedResponse($response);

        // Start blocks looping
        $builder = $this->receiptParserUtility->createLineGroupsOfSymbolsFromBlocks($blocks, $blocksOrientation, $yThreshold);

        // COMPOSE THE SENTENCES BY SYMBOLS AND SYMBOL'S METADATA PER BLOCK //
        foreach ($builder->getLines() as $lineKey=>$line) {

            $builderStartY = null;
            $builderEndY = null;
            $builderStartX = null;
            $builderEndX = null;

            $builderCompose = new BlockLineCompose();

            foreach($line->getContent() as $builderSymbol) {
                // Keep track of start and end of line coordinates
                /**
                 * @var Symbol $builderSymbol
                 */
                if (true === $builderSymbol->isFirstSymbolOfBlockLine() && null === $builderStartY) {
                    $builderStartY = $builderSymbol->getSymbolY();
                    $builderStartX = $builderSymbol->getSymbolX();
                }

                if (true === $builderSymbol->isLastSymbolOfBlockLine() && null === $builderEndY) {
                    $builderEndY = $builderSymbol->getSymbolY();
                    $builderEndX = $builderSymbol->getSymbolX();
                }

                if (true === $builderSymbol->isStartOfTheWord() && false === $builderSymbol->isFirstSymbolOfBlockLine()) {
                    $builderCompose->setDescription($builderCompose->getDescription() . " " . $builderSymbol->getText());
                } else {
                    $builderCompose->setDescription($builderCompose->getDescription() . $builderSymbol->getText());
                }
                $builderCompose->setBlockLineStartY($builderStartY);
                $builderCompose->setBlockLineEndY($builderEndY);
                $builderCompose->setBlockLineStartX($builderStartX);
                $builderCompose->setBlockLineEndX($builderEndX);

            }
            $builder->addLineComposed($builderCompose);
        }

        // COMPOSE FULL LINES AMONG BLOCKS BY LINES COMPOSED PREVIOUSLY PER BLOCK //
        $linesComposedTempBase = $builder->getLinesComposed();
        $counter = 0;
        foreach ($linesComposedTempBase as $blockKeyA=>$blockA) {
            $linesComposedTemp = $linesComposedTempBase;

            /**
             * @var BlockLineCompose $blockA
             * @var BlockLineCompose $blockB
             */
            foreach ($linesComposedTemp as $blockKeyB=>$blockB) {
                if ($blockKeyA == $blockKeyB) {
                    continue;
                }

                if (abs($blockA->getBlockLineStartY() - $blockB->getBlockLineEndY()) <= $thresholdIndicatorForSameLine) { // Means  that A is in same line with B
                    $lineY = ($blockA->getBlockLineStartY() + $blockB->getBlockLineEndY()) / 2;
                    $lineStartX = $blockB->getBlockLineStartX();
                    $lineEndX = $blockA->getBlockLineEndX();
                    $resultLineByKey = $builder->getResultLineByKeyOrNull($counter);

                    if ($blocksOrientation === ReceiptParserUtility::BLOCK_ORIENTATION_ZERO_DEG) {
                        if ($blockA->getBlockLineStartX() > $blockB->getBlockLineStartX()) {
                            if (null !== $resultLineByKey) {
                                $description = $blockB->getDescription() . " " . $resultLineByKey->getText();
                            } else {
                                $description = $blockB->getDescription() . " " . $blockA->getDescription();
                            }
                        } else {
                            if (null !== $resultLineByKey) {
                                $description = $resultLineByKey->getText() . " " . $blockB->getDescription();
                            } else {
                                $description = $blockA->getDescription() . " " . $blockB->getDescription();
                            }
                        }
                    } else {
                        if ($blockA->getBlockLineStartX() > $blockB->getBlockLineStartX()) {
                            if (null !== $resultLineByKey) {
                                $description = $resultLineByKey->getText() . " " . $blockB->getDescription();
                            } else {
                                $description = $blockB->getDescription() . " " . $blockA->getDescription();
                            }
                        } else {
                            if (null !== $resultLineByKey) {
                                $description = $blockB->getDescription() . " " . $resultLineByKey->getText();
                            } else {
                                $description = $blockB->getDescription() . " " . $blockA->getDescription();
                            }
                        }
                    }

                    $resultLine = new ResultLine();
                    $resultLine->setText($description);
                    $resultLine->setLineEndX($lineEndX);
                    $resultLine->setLineStartX($lineStartX);
                    $resultLine->setLineY($lineY);

                    unset($linesComposedTempBase[$blockKeyA]);
                    unset($linesComposedTempBase[$blockKeyB]);

                    $builder->addResultLine($resultLine);
                }
            }
            $counter++;
        }

        foreach ($linesComposedTempBase as $blockA) {
            $lineY = ($blockA->getBlockLineStartY() + $blockA->getBlockLineEndY()) / 2;
            $lineStartX = $blockA->getBlockLineStartX();
            $lineEndX = $blockA->getBlockLineEndX();
            $description = $blockA->getDescription();

            $resultLine = new ResultLine();
            $resultLine->setText($description);
            $resultLine->setLineEndX($lineEndX);
            $resultLine->setLineStartX($lineStartX);
            $resultLine->setLineY($lineY);

            $builder->addResultLine($resultLine);
        }

        // Order by line Y coordinates
        $mergedLines = [];

        foreach ($builder->getResultLines() as $key => $resultLine) {
            /**
             * @var ResultLine $resultLine
             */
            $mergedLines[$key]['text'] = $resultLine->getText();
            $mergedLines[$key]['lineY'] = $resultLine->getLineY();
            $mergedLines[$key]['lineStartX'] = $resultLine->getLineStartX();
            $mergedLines[$key]['lineEndX'] = $resultLine->getLineEndX();
        }

        $linesYCoordinate = array_column($mergedLines, 'lineY');
        array_multisort($linesYCoordinate, SORT_ASC, $mergedLines);


        // Reset the result lines after order completion
        $builder->setResultLines($mergedLines);

        echo '<pre>';
        foreach ($builder->getResultLines() as $resultLine) {
            echo $resultLine->getText() . "\n";
        }
        echo '</pre>';
    }
}
