<?php
namespace App\Entity;

class UserEntity
{



    private $roles = [
        'ROLE_USER' => 1,
        'ROLE_AUTHED' => 2,
        'ROLE_READER' => 4,
        'ROLE_ADMIN_READONLY' => 8,
        'ROLE_EDITOR' => 16,
        'ROLE_EDITOR_ASSETS' => 32,
        'ROLE_ADMIN' => 64,
        'ROLE_SUPER_ADMIN' => 128
    ];
    private string $login;
    private string $name;
    private string $role;
    private ?string $domain;
    private bool $isUserAuthed;

    public function __construct($login, $role = 'ROLE_USER', $domain = null)
    {
        if (isset($this->roles[$role])) {
            $this->role = $role;
        } else
            die('user role unknown');
        $this->domain = $domain;
        $this->login = $login;

    }
    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @param string $login 
     * @return self
     */
    public function setLogin(string $login): self
    {
        $this->login = $login;
        return $this;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role 
     * @return self
     */
    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function isGranted(string $role): bool
    {
        return ($this->role <= $role);
    }

}