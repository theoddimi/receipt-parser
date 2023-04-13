<?php

namespace Theod\CloudVisionClient\Collections\ReceiptParser;

use Theod\CloudVisionClient\Builder\ResultLine;
use Theod\CloudVisionClient\Collections\CloudVisionCollection;
use Exception;

class ResultLineCollection extends CloudVisionCollection
{
    public function __construct(array $items = [])
    {
       foreach ($items as $item) {
            if ($item instanceof ResultLine) {
                $this->items[] = $item;
            } else {
                if (is_object($item)) {
                    throw new Exception('Item given to collection is an object instance of class ' . $item::class .
                        '. Only objects of class ' . ResultLine::class . ' are allowed.');
                }

                throw new Exception('Item given to collection is type of' . gettype($item) .
                    '. Only objects of class ' . ResultLine::class . ' are allowed.');
            }
        }
    }

    /**
     * @param ResultLine $resultLine
     * @return $this
     */
    public function addItem(ResultLine $resultLine): ResultLineCollection
    {
        $this->items[] = $resultLine;

        return $this;
    }
}
