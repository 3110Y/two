<?php


namespace App\Service\OTP;


use Exception;
use LogicException;

class OTPGenerator
{
    /**
     * Генерирует код заданной длины
     * @param int $length длина кода
     * @param bool $unique уникальность цифр
     * @return int код
     * @throws Exception
     */
    public function getCode(int $length, bool $unique = false): int
    {
        return $unique ? static::getUniqueCode($length) : static::getNotUniqueCode($length);
    }

    /**
     * Генерирует Последовательность заданной длины из уникальных цифр (1234, 5689 ....)
     * @param int $length
     * @return int
     */
    protected static function getUniqueCode(int $length = 6): int
    {
        if ($length > 9) {
            throw new LogicException("maximum length of a unique code must not exceed 9", 500);
        }
        $array  = range(0,9);
        $offset = 0;
        shuffle($array);
        if ($array[0] === 0) {
            $offset = 1;
        }
        return (int) implode(array_slice($array, $offset, $length));
    }

    /**
     * Генерирует Последовательность заданной длины из цифр (1122, 1456 ....)
     * @param int $length
     * @return int
     * @throws Exception
     */
    protected static function getNotUniqueCode(int $length = 6): int
    {
        $start = 10 ** ($length - 1);
        $end = $start * 10 - 1;
        return random_int($start, $end);
    }
}
