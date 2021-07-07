<?php

namespace App\Controller;


use App\Service\Auth\AuthResponseService;
use App\Service\Auth\AuthService;
use App\Service\Http\HandlerThrowableService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class AuthController
{

    private AuthService $authService;

    /**
     * @var HandlerThrowableService
     */
    private HandlerThrowableService $handlerThrowable;
    /**
     * @var AuthResponseService
     */
    private AuthResponseService $authResponseService;

    public function __construct(AuthService $authService, HandlerThrowableService $handlerThrowable, AuthResponseService $authResponseService)
    {
        $this->authService = $authService;
        $this->handlerThrowable = $handlerThrowable;
        $this->authResponseService = $authResponseService;
    }

    /**
     * Авторизация в системе
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            $user = $this->authService->authenticate($username, $password);
            $accessToken = $this->authService->generateAccessToken($user);
            $refreshToken = $this->authService->generateRefreshToken($user);
            return $this->authResponseService->handleLoginResponse(new JsonResponse(),
                $accessToken,
                $refreshToken,
                $this->authService->getAccessTokenTTL(),
                $this->authService->getRefreshTokenTTL()
            );
        } catch (Throwable $e) {
            return $this->handlerThrowable->handle($e);
        }
    }

    /**
     * Деавторизация (выход)
     * @return JsonResponse
     */
    public function unAuthenticate(): JsonResponse
    {
        return $this->authResponseService->handleLogoutResponse(new JsonResponse());
    }

    /**
     * Обновление токенов доступа
     * @param Request $request
     * @return JsonResponse
     */
    public function updateTokens(Request $request): JsonResponse
    {
        try {
            $refreshToken = $request->cookies->get(AuthResponseService::REFRESH_TOKEN_COOKIE_KEY) ?? '';
            $accessToken = $this->authService->regenerateAccessToken($refreshToken);
            $refreshToken = $this->authService->regenerateRefreshToken($refreshToken);
            return $this->authResponseService->handleLoginResponse(new JsonResponse(),
                $accessToken,
                $refreshToken,
                $this->authService->getAccessTokenTTL(),
                $this->authService->getRefreshTokenTTL()
            );
        } catch (Throwable $e) {
            return $this->handlerThrowable->handle($e);
        }
    }

    /**
     * Проверка access токена
     * @param Request $request
     * @return JsonResponse
     */
    public function checkAccessToken(Request $request): JsonResponse
    {
        try {
            $token = $this->authService->extractTokenFromRequest($request);
            $this->authService->authorize($token);
            return new JsonResponse();
        } catch (Throwable $e) {
            return $this->handlerThrowable->handle($e);
        }
    }
}
