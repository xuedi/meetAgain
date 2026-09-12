<?php declare(strict_types=1);

namespace Plugin\Glossary\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'plg_glossary_definition')]
#[ORM\UniqueConstraint(name: 'uniq_glossary_definition_lang_entry', columns: ['language', 'glossary_id'])]
class Definition
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'definitions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Glossary $glossary = null;

    #[ORM\Column(length: 2)]
    private ?string $language = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $text = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGlossary(): ?Glossary
    {
        return $this->glossary;
    }

    public function setGlossary(?Glossary $glossary): static
    {
        $this->glossary = $glossary;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }
}
