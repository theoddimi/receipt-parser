<?php

namespace Theod\ReceiptParser\Enums;

use Exception;

/**
 * @package App\Utility
 */
abstract class Enum {

    const ARRAY_KEY_VALUE = 'value';
    const ARRAY_KEY_LABEL = 'label';

    /**
     * @var array
     */
    protected static $options = [];

    /**
     * @var string
     */
    protected $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->assertValueIsValid($value);

        $this->value = $value;
    }

    /**
     * @param mixed $input
     *
     * @return bool
     */
    public static function isValidOption($input): bool
    {
        return is_string($input) && in_array($input, static::$options);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function  getOptions(): array
    {
        return static::$options;
    }

    /**
     * @param string $value
     *
     * @throws Exception
     */
    private function assertValueIsValid(string $value): void
    {
        if (!self::isValidOption($value)) {
            throw new Exception(
                            "Invalid value for creation of a " . static::class . " enum. " . $value .
                            " used while only one of: " . implode("|", static::getOptions()) . " is allowed."
            );
        }
    }
}
