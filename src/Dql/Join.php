<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Dql;

use BenRowan\DoctrineAssert\Exceptions\DoctrineAssertException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join as DoctrineJoin;

class Join
{
    private $queryBuilder;

    private $entityManager;

    public function __construct(
        QueryBuilder $queryBuilder,
        EntityManagerInterface $entityManager
    ) {
        $this->queryBuilder  = $queryBuilder;
        $this->entityManager = $entityManager;
    }

    public function add(
        string $childEntityFqn,
        string $childAlias,
        string $parentEntityFqn,
        string $parentAlias
    ): void {

        $mapping = $this->findMapping(
            $childEntityFqn,
            $childAlias,
            $parentEntityFqn,
            $parentAlias
        );

        $this->buildJoin($mapping, $childEntityFqn, $childAlias);
    }

    private function findMapping(
        string $childEntityFqn,
        string $childAlias,
        string $parentEntityFqn,
        string $parentAlias
    ): array {

        $parentMetaData = $this->entityManager->getClassMetadata($parentEntityFqn);
        $childMetaData  = $this->entityManager->getClassMetadata($childEntityFqn);

        $allMappings = array_merge(
            $parentMetaData->getAssociationMappings(),
            $childMetaData->getAssociationMappings()
        );

        $owningSideMappings = array_filter(
            $allMappings,
            function ($associationMapping) {
                return $associationMapping['isOwningSide'];
            }
        );

        foreach ($owningSideMappings as $mapping) {

            $targetEntity = $mapping['targetEntity'];

            if ($childEntityFqn === $targetEntity) {
                return [
                    'ownedByAlias'    => $childAlias,
                    'inversedByAlias' => $parentAlias,
                    'mapping'         => $mapping
                ];
            }

            if ($parentEntityFqn === $targetEntity) {
                return [
                    'ownedByAlias'    => $parentAlias,
                    'inversedByAlias' => $childAlias,
                    'mapping'         => $mapping
                ];
            }
        }

        throw new DoctrineAssertException(
            "No mapping found for '$parentEntityFqn' with '$childEntityFqn'"
        );
    }

    private function buildJoin(array $mapping, string $childEntityFqn, string $childAlias): void
    {
        $condition = $this->buildJoinCondition($mapping);

        $this->queryBuilder->join(
            $childEntityFqn,
            $childAlias,
            DoctrineJoin::WITH,
            $condition
        );
    }

    private function buildJoinCondition(array $mapping)
    {
        $ownedByAlias    = $mapping['ownedByAlias'];
        $inversedByAlias = $mapping['inversedByAlias'];
        $fieldName       = $mapping['mapping']['fieldName'];

        $conditions = array_map(
            function (array $joinColumn) use ($ownedByAlias, $inversedByAlias, $fieldName) {
                return "$ownedByAlias.{$joinColumn['referencedColumnName']} = $inversedByAlias.$fieldName";
            },
            $mapping['mapping']['joinColumns']
        );

        return implode(' AND ', $conditions);
    }
}