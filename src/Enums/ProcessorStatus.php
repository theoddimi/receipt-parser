<?php

namespace Theod\ReceiptParser\Enums;

class ProcessorStatus extends Enum
{
    const SUCCESS = 'succ';
    const IN_PROGRESS = 'prog';
    const FAILED = 'fail';

    /**
     * @var array
     */
    protected static $options = [
        self::SUCCESS,
        self::IN_PROGRESS,
        self::FAILED,
    ];
}
