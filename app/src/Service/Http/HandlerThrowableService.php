<?php

namespace App\Service\Http;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class HandlerThrowableService
{
    protected string $env;

    protected array $answer = [
        'success' => false,
        'message' => ''
    ];

    public function __construct(string $env)
    {
        $this->env = $env;
    }

    public function handle(Throwable $throwable): JsonResponse
    {
        $this->answer['message'] = $throwable->getMessage();
        $code = $throwable->getCode();
        if ($code < 400 || $code > 526) {
            $code = 500;
        }
        if ($throwable instanceof HttpException) {
            $code = $throwable->getStatusCode();
        }
        if ($this->env !== 'prod') {
            $this->answer['error'] = $throwable->getFile() . ':' . $throwable->getLine();
            $this->answer['trace'] = $throwable->getTrace();
        }
        return new JsonResponse($this->answer, $code);
    }
}