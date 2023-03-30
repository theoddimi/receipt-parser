<?php

namespace Theod\CloudVisionClient\Builder;

class BlockLineCompose
{
   private string $description = '';
   private float $blockLineStartY;
   private float $blockLineEndY;
   private float $blockLineStartX;
   private float $blockLineEndX;

//   private array $composed;

    public function __construct() {}

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return float
     */
    public function getBlockLineStartY(): float
    {
        return $this->blockLineStartY;
    }

    /**
     * @param float $blockLineStartY
     */
    public function setBlockLineStartY(float $blockLineStartY): void
    {
        $this->blockLineStartY = $blockLineStartY;
    }

    /**
     * @return float
     */
    public function getBlockLineEndY(): float
    {
        return $this->blockLineEndY;
    }

    /**
     * @param float $blockLineEndY
     */
    public function setBlockLineEndY(float $blockLineEndY): void
    {
        $this->blockLineEndY = $blockLineEndY;
    }

    /**
     * @return float
     */
    public function getBlockLineStartX(): float
    {
        return $this->blockLineStartX;
    }

    /**
     * @param float $blockLineStartX
     */
    public function setBlockLineStartX(float $blockLineStartX): void
    {
        $this->blockLineStartX = $blockLineStartX;
    }

    /**
     * @return float
     */
    public function getBlockLineEndX(): float
    {
        return $this->blockLineEndX;
    }

    /**
     * @param float $blockLineEndX
     */
    public function setBlockLineEndX(float $blockLineEndX): void
    {
        $this->blockLineEndX = $blockLineEndX;
    }
}