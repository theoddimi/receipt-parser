<?php

namespace Theod\CloudVisionClient\Builder;

class ResultLine
{
    private string $text;
    private float $lineY;
    private float $lineStartX;
    private float $lineEndX;

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return float
     */
    public function getLineY(): float
    {
        return $this->lineY;
    }

    /**
     * @param float $lineY
     */
    public function setLineY(float $lineY): void
    {
        $this->lineY = $lineY;
    }

    /**
     * @return float
     */
    public function getLineStartX(): float
    {
        return $this->lineStartX;
    }

    /**
     * @param float $lineStartX
     */
    public function setLineStartX(float $lineStartX): void
    {
        $this->lineStartX = $lineStartX;
    }

    /**
     * @return float
     */
    public function getLineEndX(): float
    {
        return $this->lineEndX;
    }

    /**
     * @param float $lineEndX
     */
    public function setLineEndX(float $lineEndX): void
    {
        $this->lineEndX = $lineEndX;
    }
}
