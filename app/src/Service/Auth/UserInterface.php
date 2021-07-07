<?php

namespace App\Service\Auth;


interface UserInterface
{
    /**
     * Идентификатор пользователя
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Логин пользователя
     * @return string
     */
    public function getLogin(): string;

    /**
     * Проверяет совпадение пароля с хэшем в БД
     * @param string $plainPassword
     * @return bool
     */
    public function checkPassword(string $plainPassword): bool;

    /**
     * Список ключей ролей в виде массива строк
     * @return string[]
     */
    public function getRoleList(): array;
}