<?php

namespace Theod\ReceiptParser\Processor;

use Theod\ReceiptParser\Enums\ProcessorStatus;
use Theod\ReceiptParser\Processor\Contracts\ReceiptParserProcessorInterface;

abstract class Processor  implements ReceiptParserProcessorInterface
{
    private float $start;
    private float $end;
    private float $executionDuration;
    private ProcessorStatus $status;

    /**
     * @return void
     */
    protected function start(): void
    {
        $this->start =  microtime(true);
    }

    /**
     * @return void
     */
    protected function end(): void
    {
        $this->end = microtime(true);
        $this->calculateExecutionDuration();
    }

    /**
     * @return void
     */
    private function calculateExecutionDuration(): void
    {
        $this->executionDuration = $this->end - $this->start;
    }

    public function getExecutionDuration(): float
    {
        return $this->executionDuration;
    }

    /**
     * @return ProcessorStatus
     */
    protected function getStatus(): ProcessorStatus
    {
        return $this->status;
    }

    /**
     * @param ProcessorStatus $status
     */
    protected function setStatus(ProcessorStatus $status): void
    {
        $this->status = $status;
    }

    abstract function run();
}