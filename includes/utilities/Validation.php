<?php

namespace BemaGoalForge\Utilities;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Validation
{
    /**
     * Validate if a string is a valid date in the format 'Y-m-d'.
     *
     * @param string $date
     * @return bool
     */
    public static function isValidDate(string $date): bool
    {
        $format = 'm-d-Y'; // Expected date format
        $dateTime = \DateTime::createFromFormat($format, $date);

        return $dateTime && $dateTime->format($format) === $date;
    }

        /**
     * Validate if a value is a non-zero positive integer.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isNonZeroInteger($value): bool
    {
        return is_int($value) && $value > 0;
    }
}
