<?php


namespace App\Service\Auth;


use JetBrains\PhpStorm\Pure;
use RuntimeException;
use Throwable;

/**
 * Ошибка аутентификации (например неправильный пароль)
 */
class AuthenticationException extends RuntimeException
{
    #[Pure] public function __construct($message = "", Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}