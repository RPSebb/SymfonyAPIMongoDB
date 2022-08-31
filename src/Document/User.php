<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[MongoDB\Document(collection:'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface {

    #[MongoDB\Id]
    private $id;

    #[Assert\Email]
    #[Assert\Unique]
    #[Assert\NotNull]
    #[MongoDB\Field(type: 'string')]
    private $email;

    // #[MongoDB\Field(type: 'json')]
    private $roles = [];

    /**
     * @var string The hashed password
     */
    #[Assert\Type('string')]
    #[Assert\NotNull]
    #[MongoDB\Field(type: 'string')]
    private $password;

    // public function getId(): ?int {
    //     return $this->id;
    // }

    // public function getEmail(): ?string {
    //     return $this->email;
    // }

    // public function setEmail(string $email): self {
    //     $this->email = $email;

    //     return $this;
    // }

    /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    // public function setRoles(array $roles): self {
    //     $this->roles = $roles;

    //     return $this;
    // }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string {
        return $this->password;
    }

    // public function setPassword(string $password): self {
    //     $this->password = $password;

    //     return $this;
    // }

    // /**
    //  * Returning a salt is only needed, if you are not using a modern
    //  * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
    //  *
    //  * @see UserInterface
    //  */
    // public function getSalt(): ?string {
    //     return null;
    // }

    /**
     * @see UserInterface
     */
    public function eraseCredentials() {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}