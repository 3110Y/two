<?php


namespace App\Service\Auth;


use DateTime;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthResponseService
{
    public const REFRESH_TOKEN_COOKIE_KEY = 'refresh-token';

    public const REFRESH_TOKEN_COOKIE_PATH = '/api/auth';

    protected string $env;

    public function __construct(string $env)
    {
        $this->env = $env;
    }

    /**
     * @param JsonResponse $response
     * @param string $accessToken
     * @param string $refreshToken
     * @param int $accessTokenTTL
     * @return JsonResponse
     */
    public function handleLoginResponse(JsonResponse $response, string $accessToken, string $refreshToken, int $accessTokenTTL, int $refreshTokenTTL): JsonResponse
    {
        $response->setData([
            'success' => true,
            'result' => [
                'token' => $accessToken,
                'ttl' => $accessTokenTTL,
            ]
        ]);
        $response->headers->setCookie(new Cookie(
            static::REFRESH_TOKEN_COOKIE_KEY,
            $refreshToken,
            (new DateTime())->modify(sprintf("+%d seconds", $refreshTokenTTL)),
            '/api/auth',
            null,
            $this->env  === 'prod',
            true,
            false,
            "Strict"
        ));

        return $response;
    }

    public function handleLogoutResponse(JsonResponse $response): JsonResponse
    {
        $response->headers->clearCookie(static::REFRESH_TOKEN_COOKIE_KEY,
            self::REFRESH_TOKEN_COOKIE_PATH,
            null,
            !$this->env,
            true,
            'Strict'
        );
        return $response;
    }
}