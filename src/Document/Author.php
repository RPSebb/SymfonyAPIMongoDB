<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[MongoDB\Document(collection:'authors')]
class Author {

    #[MongoDB\Id]
    #[Groups(['show'])]
    protected $id;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[MongoDB\Field(type: 'string')]
    #[Groups(['show', 'edit'])]
    protected $surname;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[MongoDB\Field(type: 'string')]
    #[Groups(['show', 'edit'])]
    protected $name;

    public function getId(): ?string {
        return $this->id;
    }

    public function getSurname(): ?string {
        return $this->surname;
    }

    public function setSurname(string $surname): self {
        $this->surname = $surname;
        return $this;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }
}