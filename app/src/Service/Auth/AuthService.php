<?php

namespace App\Service\Auth;

use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Lcobucci\JWT\Validation\Constraint;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Lcobucci\JWT\Configuration;

class AuthService
{
    private Configuration $configuration;

    public function __construct(
        /** Время жизни токена доступа в секундах */
        private string $accessTokenTTL,
        /** Время жизни refresh токена в секундах */
        private string $refreshTokenTTL,
        /** Приватный ключ */
        private string $privatePemPath,
        /** Публичный ключ */
        private string $publicPemPath,
        /** Приватная парольная фраза */
        private string $passPhrase,
        /** Класс сущности пользователя */
        private string $userEntityClass,
        private EntityManagerInterface $entityManager
    )
    {
        $privateKey = LocalFileReference::file($this->privatePemPath, $this->passPhrase);
        $publicKey = LocalFileReference::file($this->publicPemPath, $this->passPhrase);
        $signer = new Sha512();
        $this->configuration = Configuration::forAsymmetricSigner(
            $signer,
            $privateKey,
            $publicKey,
        );
        $this->configuration->setValidationConstraints(
            new Constraint\SignedWith($signer, $publicKey)
        );
    }

    /**
     * Аутентификация (вход) пользователя
     * @param string $username логин пользователя
     * @param string $password пароль пользователя
     * @return UserInterface
     * @throws AuthenticationException в случае некорректных данных
     */
    public function authenticate(string $username, string $password): UserInterface
    {
        if (empty($this->userEntityClass)) {
            throw new AuthenticationException('Не передан класс сущности пользователя');
        }
        if (empty($username)) {
            throw new AuthenticationException('Поле "логин" обязательно для заполнения');
        }
        if (empty($password)) {
            throw new AuthenticationException('Поле "пароль" обязательно для заполнения');
        }
        try {
            $userRepository = $this->entityManager->getRepository($this->userEntityClass);
        } catch (Exception $exception) {
            throw new AuthenticationException(sprintf('Не найден репозиторий пользователей для сущности "%s"', $this->userEntityClass), $exception);
        }
        if (!$userRepository instanceof UserRepositoryInterface) {
            throw new AuthenticationException(sprintf('Репозиторий пользователей "%s" некорректен', $userRepository::class));
        }
        $user = $userRepository->findUserByLogin($username);
        if ($user === null) {
            throw new AuthenticationException('Введен неверный логин или пароль');
        }
        if (!$user instanceof UserInterface) {
            throw new AuthenticationException(sprintf('Тип пользователя «%s» не поддерживается', $this->userEntityClass));
        }

        if (!$user->checkPassword($password)) {
            throw new AuthenticationException('Введен неверный логин или пароль');
        }
        return $user;
    }

    /**
     * Авторизация пользователя (проверка токена доступа, получение пользователя)
     * @throws AuthorizationException В случае ошибки авторизации
     */
    public function authorize(string $accessToken): UserInterface
    {
        $payload = $this->parseToken($accessToken);
        return $this->fetchUser($payload['id']);
    }

    /**
     * Проверяет валидность токена
     */
    public function isValidToken(string $token): bool
    {
        try {
            $parser = $this->configuration->parser();
            $jwt = $parser->parse($token);
            if ($jwt->isExpired(new DateTimeImmutable())) {
                return false;
            }
            return $this->configuration->validator()->validate($jwt, ...$this->configuration->validationConstraints());
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @throws RuntimeException
     * @throws AuthorizationException
     */
    public function generateAccessToken(UserInterface $user): string
    {
        return $this->createToken($user, $this->accessTokenTTL, ['roles' => $user->getRoleList()]);
    }

    /**
     * Генерирует новый access токен
     * @throws RuntimeException
     * @throws AuthorizationException
     */
    public function regenerateAccessToken(string $refreshToken): string
    {
        if (!$this->isValidToken($refreshToken)) {
            throw new AuthorizationException('Токен невалиден');
        }
        $tokenPayload = $this->parseToken($refreshToken);
        if (empty($tokenPayload['refresh'])) {
            throw new AuthorizationException('Токен невалиден');
        }
        $user = $this->fetchUser($tokenPayload['id']);
        return $this->generateAccessToken($user);
    }

    /**
     * Генерирует новый refresh токен
     * @throws RuntimeException
     * @throws AuthorizationException
     */
    public function generateRefreshToken(UserInterface $user): string
    {
        return $this->createToken($user, $this->refreshTokenTTL, ['refresh' => true]);
    }

    /**
     * Генерирует новый refresh токен
     * @throws RuntimeException
     * @throws AuthorizationException
     */
    public function regenerateRefreshToken(string $refreshToken): string
    {
        if (!$this->isValidToken($refreshToken)) {
            throw new AuthorizationException('Токен невалиден');
        }
        $tokenPayload = $this->parseToken($refreshToken);
        if (empty($tokenPayload['refresh'])) {
            throw new AuthorizationException('Токен невалиден');
        }
        $user = $this->fetchUser($tokenPayload['id']);
        return $this->generateRefreshToken($user);
    }

    /**
     * Получает текущего пользователя (по данным из токена)
     * @throws AuthorizationException
     */
    protected function fetchUser(int $userId): UserInterface
    {
        try {
            $userRepository = $this->entityManager->getRepository($this->userEntityClass);
        } catch (Exception $exception) {
            throw new AuthenticationException(sprintf('Не найден репозиторий пользователей для сущности "%s"', $this->userEntityClass), $exception);
        }
        if (!$userRepository instanceof UserRepositoryInterface) {
            throw new AuthenticationException(sprintf('Репозиторий пользователей "%s" некорректен', $userRepository::class));
        }
        $user = $userRepository->findUserById($userId);
        if ($user === null) {
            throw new AuthorizationException(sprintf('Пользователь %s(%d) не найден', $this->userEntityClass, $userId));
        }
        if (!$user instanceof UserInterface) {
            throw new AuthenticationException(sprintf('Тип пользователя «%s» не поддерживается', $this->userEntityClass));
        }
        return $user;
    }


    public function getAccessTokenTTL(): int
    {
        return $this->accessTokenTTL;
    }

    public function getRefreshTokenTTL(): int
    {
        return $this->refreshTokenTTL;
    }

    /**
     * Извлекает токен из Authorization header запроса (при наличии)
     * @param Request $request
     * @return string|null
     */
    public function extractTokenFromRequest(Request $request): ?string
    {
        $authorization = $request->headers->get('Authorization');
        $prefix = 'Bearer ';
        if (!$authorization || !str_starts_with($authorization, $prefix)) {
            return null;
        }
        return trim(substr($authorization, strlen($prefix)));
    }

    /**
     * Валидирует токен и декодирует payload
     * @param string $token
     * @return array
     * @throws AuthorizationException
     */
    protected function parseToken(string $token): array
    {
        try {
            $parser = $this->configuration->parser();
            $jwt = $parser->parse($token);
        } catch (Exception $exception) {
            throw new AuthorizationException('Токен невалиден', $exception);
        }
        if ($jwt->isExpired(new DateTimeImmutable())) {
            throw new AuthorizationException('Токен просрочен');
        }

        $payload = $jwt->claims()->all();

        if (empty($payload['id'])) {
            throw new AuthorizationException('Токен невалиден');
        }

        try {
            $verified = $this->configuration->validator()->validate($jwt, ...$this->configuration->validationConstraints());
        } catch (Exception $exception) {
            throw new AuthorizationException('Токен невалиден', $exception);
        }
        if (!$verified) {
            throw new AuthorizationException('Токен невалиден');
        }
        return $payload;
    }

    /**
     * Генерирует токен с переданной нагрузкой
     * @throws AuthorizationException
     * @throws RuntimeException
     */
    protected function createToken(UserInterface $user, int $ttl, array $payload = []): string
    {
        $payload = array_merge([
            'id' => $user->getId(),
            'username' => $user->getLogin()
        ], $payload);


        $jws = $this->configuration->builder();
        $jws->issuedAt(new DateTimeImmutable());
        try {
            $jws->expiresAt((new DateTimeImmutable())->add(new DateInterval(sprintf('PT%dS', $ttl))));
        } catch (Exception $exception) {
            throw new RuntimeException('Невалидный ttl для токена', 500, $exception);
        }
        foreach ($payload as $name => $value) {
            $jws->withClaim($name, $value);
        }
        try {
            return $jws->getToken($this->configuration->signer(), $this->configuration->signingKey())->toString();
        } catch (Exception $exception) {
            throw new RuntimeException('Невозможно создать токен', 500, $exception);
        }
    }
}