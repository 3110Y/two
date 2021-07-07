<?php

namespace App\Controller;


use App\Service\Auth\AuthorizationException;
use App\Service\Auth\AuthService;
use App\Service\Http\HandlerThrowableService;
use App\Service\OTP\OTPGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Symfony\Component\HttpFoundation\Request;

class OTPController
{
    private OTPGenerator $OTPGenerator;

    private HandlerThrowableService $handlerThrowable;

    private AuthService $authService;

    public function __construct(OTPGenerator $OTPGenerator, HandlerThrowableService $handlerThrowable, AuthService $authService)
    {
        $this->OTPGenerator = $OTPGenerator;
        $this->handlerThrowable = $handlerThrowable;
        $this->authService = $authService;
    }


    public function getOTPGenerator(Request $request): Response
    {
        try {
            $token = $this->authService->extractTokenFromRequest($request);
            if (!$this->authService->isValidToken($token)) {
                throw new AuthorizationException();
            }
            return new JsonResponse([
                'success' => true,
                'code' => $this->OTPGenerator->getCode(4, true)
            ]);
        } catch (Throwable $throwable) {
            return $this->handlerThrowable->handle($throwable);
        }
    }
}