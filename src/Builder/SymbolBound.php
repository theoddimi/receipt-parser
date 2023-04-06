<?php

namespace Theod\CloudVisionClient\Builder;

class SymbolBound
{
    private float $symbolTopYBoundAvg;
    private float $symbolBottomYBoundAvg;
    private float $symbolLeftXBoundAvg;
    private float $symbolRightXBoundAvg;

    /**
     * @return float
     */
    public function getSymbolTopYBoundAvg(): float
    {
        return $this->symbolTopYBoundAvg;
    }

    /**
     * @param float $symbolTopYBoundAvg
     */
    public function setSymbolTopYBoundAvg(float $symbolTopYBoundAvg): void
    {
        $this->symbolTopYBoundAvg = $symbolTopYBoundAvg;
    }

    /**
     * @return float
     */
    public function getSymbolBottomYBoundAvg(): float
    {
        return $this->symbolBottomYBoundAvg;
    }

    /**
     * @param float $symbolBottomYBoundAvg
     */
    public function setSymbolBottomYBoundAvg(float $symbolBottomYBoundAvg): void
    {
        $this->symbolBottomYBoundAvg = $symbolBottomYBoundAvg;
    }

    /**
     * @return float
     */
    public function getSymbolLeftXBoundAvg(): float
    {
        return $this->symbolLeftXBoundAvg;
    }

    /**
     * @param float $symbolLeftXBoundAvg
     */
    public function setSymbolLeftXBoundAvg(float $symbolLeftXBoundAvg): void
    {
        $this->symbolLeftXBoundAvg = $symbolLeftXBoundAvg;
    }

    /**
     * @return float
     */
    public function getSymbolRightXBoundAvg(): float
    {
        return $this->symbolRightXBoundAvg;
    }

    /**
     * @param float $symbolRightXBoundAvg
     */
    public function setSymbolRightXBoundAvg(float $symbolRightXBoundAvg): void
    {
        $this->symbolRightXBoundAvg = $symbolRightXBoundAvg;
    }
}
