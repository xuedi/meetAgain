<?php

namespace App\Entity;

use App\Repository\CmsBlockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CmsBlockRepository::class)]
class CmsBlock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2)]
    private ?string $Language = null;

    #[ORM\Column(enumType: CmsBlockTypes::class)]
    private ?CmsBlockTypes $Type = null;

    #[ORM\ManyToOne(inversedBy: 'blocks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cms $page = null;

    #[ORM\Column]
    private array $json = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLanguage(): ?string
    {
        return $this->Language;
    }

    public function setLanguage(string $Language): static
    {
        $this->Language = $Language;

        return $this;
    }

    public function getType(): ?CmsBlockTypes
    {
        return $this->Type;
    }

    public function setType(CmsBlockTypes $Type): static
    {
        $this->Type = $Type;

        return $this;
    }

    public function getPage(): ?Cms
    {
        return $this->page;
    }

    public function setPage(?Cms $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getJson(): array
    {
        return $this->json;
    }

    public function setJson(array $json): static
    {
        $this->json = $json;

        return $this;
    }
}
