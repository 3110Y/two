<?php


namespace App\Service\Auth;


use JetBrains\PhpStorm\Pure;
use RuntimeException;
use Throwable;

/**
 * Ошибка авторизации (например истёкший токен доступа)
 */
class AuthorizationException extends RuntimeException
{
    #[Pure] public function __construct($message = "", Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}