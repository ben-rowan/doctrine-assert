<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Constraints;

use BenRowan\DoctrineAssert\Config\QueryConfigIterator;
use BenRowan\DoctrineAssert\Dql\AssertJoin\AssertJoin;
use BenRowan\DoctrineAssert\Dql\AssertJoin\AssertJoinInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Constraint\Constraint;

abstract class AbstractDatabaseConstraint extends Constraint
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AssertJoinInterface
     */
    private $join;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->queryBuilder  = $entityManager->createQueryBuilder();
        $this->join          = new AssertJoin($this->queryBuilder, $this->entityManager);
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function addCountSelect(string $rootEntityFqn, string $rootAlias): void
    {
        $this->getQueryBuilder()
            ->select(
                $this->getQueryBuilder()->expr()->count($rootAlias)
            )
            ->from($rootEntityFqn, $rootAlias);
    }

    /**
     * Converts an entity fully qualified name (FQN) into a DQL alias.
     *
     * This method can also take a parents alias. This parent alias becomes
     * the namespace for the new child alias.
     *
     * @param string $fqn
     * @param null|string $parentAlias
     * @return mixed
     */
    protected function fqnToAlias(string $fqn, ?string $parentAlias = null)
    {
        $childAlias = $this->cleanAlias($fqn);

        if (null === $parentAlias) {
            return $childAlias;
        }

        return $parentAlias . '_' . $childAlias;
    }

    protected function buildQuery(
        QueryConfigIterator $queryConfig,
        string $currentEntityFqn,
        string $currentAlias
    ): void {

        if (0 === $queryConfig->count()) {
            return;
        }

        $queryConfig->next();

        if ($queryConfig->currentIsChildConfig()) {

            $this->buildChildQuery(
                $queryConfig->current(),
                $queryConfig->key(),
                $currentEntityFqn,
                $currentAlias
            );
        }

        if ($queryConfig->currentIsValue()) {

            $this->addWhere(
                $queryConfig->current(),
                $queryConfig->key(),
                $currentAlias
            );
        }

        $this->buildQuery(
            $queryConfig,
            $currentEntityFqn,
            $currentAlias
        );
    }

    /**
     * The number of results for the current query.
     *
     * @note To use this method you must be using an addCountSelect() select.
     *
     * @return int
     *
     * @throws NonUniqueResultException
     */
    protected function resultCount(): int
    {
        return (int) $this->getQueryBuilder()
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function cleanAlias(string $dirtyAlias): string
    {
        return \str_replace(
            ['\\', '.'],
            '_',
            $dirtyAlias
        );
    }

    private function buildChildQuery(
        QueryConfigIterator $queryConfig,
        string $childEntityFqn,
        string $parentEntityFqn,
        string $parentAlias
    ): void {

        $childAlias = $this->fqnToAlias($childEntityFqn, $parentAlias);

        $this->join->add(
            $childEntityFqn,
            $childAlias,
            $parentEntityFqn,
            $parentAlias
        );

        $this->buildQuery(
            $queryConfig,
            $childEntityFqn,
            $childAlias
        );
    }

    private function addWhere($value, string $field, string $alias): void
    {
        $placeholder = $alias . '_' . $this->cleanAlias($field);

        $this->getQueryBuilder()
            ->andWhere(
                $this->getQueryBuilder()->expr()->eq("$alias.$field", ":$placeholder")
            )
            ->setParameter($placeholder, $value);
    }
}
