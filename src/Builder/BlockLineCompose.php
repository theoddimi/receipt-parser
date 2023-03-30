<?php

namespace Theod\CloudVisionClient\Builder;

class BlockLineCompose
{
    /**
     * @var string
     */
    private string $description = '';

    /**
     * @var float|null
     */
    private ?float $blockLineStartY;

    /**
     * @var float|null
     */
    private ?float $blockLineEndY;

    /**
     * @var float|null
     */
    private ?float $blockLineStartX;

    /**
     * @var float|null
     */
    private ?float $blockLineEndX;

//   private array $composed;

    /**
     *
     */
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
     * @return float|null
     */
    public function getBlockLineStartY(): ?float
    {
        return $this->blockLineStartY;
    }

    /**
     * @param float|null $blockLineStartY
     */
    public function setBlockLineStartY(?float $blockLineStartY): void
    {
        $this->blockLineStartY = $blockLineStartY;
    }

    /**
     * @return float|null
     */
    public function getBlockLineEndY(): ?float
    {
        return $this->blockLineEndY;
    }

    /**
     * @param float|null $blockLineEndY
     */
    public function setBlockLineEndY(?float $blockLineEndY): void
    {
        $this->blockLineEndY = $blockLineEndY;
    }

    /**
     * @return float|null
     */
    public function getBlockLineStartX(): ?float
    {
        return $this->blockLineStartX;
    }

    /**
     * @param float|null $blockLineStartX
     */
    public function setBlockLineStartX(?float $blockLineStartX): void
    {
        $this->blockLineStartX = $blockLineStartX;
    }

    /**
     * @return float|null
     */
    public function getBlockLineEndX(): ?float
    {
        return $this->blockLineEndX;
    }

    /**
     * @param float|null $blockLineEndX
     */
    public function setBlockLineEndX(?float $blockLineEndX): void
    {
        $this->blockLineEndX = $blockLineEndX;
    }
}