<?php

namespace Theod\CloudVisionClient\Processor;

use Theod\CloudVisionClient\Enums\ProcessorStatus;
use Theod\CloudVisionClient\Objects\Uri;

abstract class Processor
{
    private float $start;
    private float $end;
    private float $executionDuration;
    private Uri $sourceUri;
    private ProcessorStatus $status;


    /**
     * @param string $sourceUri
     * @return void
     */
    public function setSourceUriToProcess(string $sourceUri): void
    {
        $this->sourceUri = new Uri($sourceUri);
    }

    /**
     * @return Uri
     */
    public function getSourceUriToProcess(): Uri
    {
        return $this->sourceUri;
    }

    /**
     * @return void
     */
    protected function start(): void
    {
        $this->start =  microtime(true);
        $this->status = new ProcessorStatus(ProcessorStatus::IN_PROGRESS);
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

    /**
     * @return void
     */
    protected function setFailed(): void
    {
        $this->status = new ProcessorStatus(ProcessorStatus::FAILED);
    }

    abstract function run();
}