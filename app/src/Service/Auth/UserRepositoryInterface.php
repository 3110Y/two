<?php

namespace App\Service\Auth;


interface UserRepositoryInterface
{
    /**
     * Осуществляет поиск пользователя по переданному login
     * @param string $login
     * @return UserInterface|null возвращает пользователя если найден, либо null в противном случае
     */
    public function findUserByLogin(string $login): ?UserInterface;

    /**
     * Осуществляет поиск пользователя по переданному id
     * @param int $id
     * @return UserInterface|null возвращает пользователя если найден, либо null в противном случае
     */
    public function findUserById(int $id): ?UserInterface;
}