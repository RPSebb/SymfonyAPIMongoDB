<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;

#[MongoDB\Document(collection: 'books')]
class Book {

    #[MongoDB\Id]
    #[Groups(['show'])]
    protected $id;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[MongoDB\Field(type: 'string')]
    #[Groups(['show', 'edit'])]
    protected $title;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[MongoDB\ReferenceMany(targetDocument: Author::class, storeAs: 'id', cascade: ['persist', 'remove'])]
    #[Groups(['show', 'edit'])]
    protected $author;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[MongoDB\Field(type: 'string')]
    #[Groups(['show', 'edit'])]
    protected $editor;

    public function __construct()
    {
        $this->author = new ArrayCollection();
    }

    public function getId(): ?string {
        return $this->id;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(string $title): self {
        $this->title = $title;
        return $this;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function setAuthor($value) {
        $this->author = $value;
        return $this;
    }

    public function addAuthor($author) {
        $this->author->add($author);

    }

    public function removeAuthor(int $key): void {
        $this->author->remove($key);
    }

    public function getEditor(): ?string {
        return $this->editor;
    }

    public function setEditor(string $editor): self {
        $this->editor = $editor;
        return $this;
    }
}