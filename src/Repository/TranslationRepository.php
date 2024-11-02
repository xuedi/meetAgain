<?php

namespace App\Repository;

use App\Entity\Translation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Translation>
 */
class TranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translation::class);
    }

    public function buildKeyValueList(): array
    {
        $list = [];
        foreach ($this->findAll() as $translation) {
            $list[$translation->getId()] = $translation->getTranslation();
        }
        return $list;
    }

    public function getUniqueList(): array
    {
        $list = [];
        foreach ($this->findAll() as $translation) {
            $list[$translation->getLanguage()][] = $translation->getPlaceholder();
        }
        return $list;
    }
}
