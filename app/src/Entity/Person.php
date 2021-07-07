<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use App\Service\Auth\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JetBrains\PhpStorm\Pure;

/**
 * @ORM\Entity(repositoryClass=PersonRepository::class)
 */
class Person implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * Фамилия
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $surname;

    /**
     * Имя
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $name;

    /**
     * Отчество
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $patronymic;

    /**
     * Логин
     * @ORM\Column(type="string", length=255)
     */
    private ?string $login;

    /**
     * Пароль
     * @ORM\Column(type="string", length=255)
     */
    private ?string $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $salt;

    /**
     * Роли
     * @ORM\ManyToMany(targetEntity=Role::class, mappedBy="people")
     */
    private Collection $roles;

    #[Pure] public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function setPatronymic(?string $patronymic): self
    {
        $this->patronymic = $patronymic;

        return $this;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(?string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * @param string|null $planPassword
     * @return $this
     * @throws Exception
     */
    public function setPlanPassword(?string $planPassword): self
    {
        if (empty($planPassword)) {
            return $this;
        }
        $this->setSalt(bin2hex(random_bytes(3)));
        $this->password = $this->generatePassword($planPassword);

        return $this;
    }

    #[Pure] public function checkPassword(string $plainPassword): bool
    {
        return $this->getPassword() === $this->generatePassword($plainPassword);
    }

    /**
     * @param string $plainPassword
     * @return string
     */
    #[Pure] private function generatePassword(string $plainPassword): string
    {
        return hash('sha512',  $this->getSalt() . '|' .  $plainPassword);
    }

    /**
     * @return Collection|Role[]
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
            $role->addPerson($this);
        }

        return $this;
    }

    public function removeRole(Role $role): self
    {
        if ($this->roles->removeElement($role)) {
            $role->removePerson($this);
        }

        return $this;
    }

    /**
     * @return array
     */
    #[Pure] public function getRoleList(): array
    {
        $roles = [];
        /** @var Role $role */
        foreach ($this->roles as $role) {
            $roles[] = $role->getName();
        }
        return $roles;
    }
}

