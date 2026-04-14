<?php

declare(strict_types=1);

namespace App\Doctrine\Orm\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Project;
use App\Entity\Sprint;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class OwnedResourceExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
  public function __construct(
    private Security $security,
  ) {
  }

  public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
  {
    $this->addOwnershipClause($queryBuilder, $queryNameGenerator, $resourceClass);
  }

  public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
  {
    $this->addOwnershipClause($queryBuilder, $queryNameGenerator, $resourceClass);
  }

  private function addOwnershipClause(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass): void
  {
    if (!in_array($resourceClass, [Project::class, Sprint::class, Task::class], true)) {
      return;
    }

    $user = $this->security->getUser();

    if (!$user instanceof User) {
      $queryBuilder->andWhere('1 = 0');

      return;
    }

    $rootAlias = $queryBuilder->getRootAliases()[0];

    match ($resourceClass) {
      Project::class => $queryBuilder->andWhere(sprintf('%s.owner = :current_user', $rootAlias)),
      Sprint::class => $this->addSprintOwnershipClause($queryBuilder, $queryNameGenerator, $rootAlias),
      Task::class => $this->addTaskOwnershipClause($queryBuilder, $queryNameGenerator, $rootAlias),
      default => null,
    };

    $queryBuilder->setParameter('current_user', $user);
  }

  private function addSprintOwnershipClause(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias): void
  {
    $projectAlias = $queryNameGenerator->generateJoinAlias('project');

    $queryBuilder
      ->innerJoin(sprintf('%s.project', $rootAlias), $projectAlias)
      ->andWhere(sprintf('%s.owner = :current_user', $projectAlias));
  }

  private function addTaskOwnershipClause(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias): void
  {
    $sprintAlias = $queryNameGenerator->generateJoinAlias('sprint');
    $projectAlias = $queryNameGenerator->generateJoinAlias('project');

    $queryBuilder
      ->innerJoin(sprintf('%s.sprint', $rootAlias), $sprintAlias)
      ->innerJoin(sprintf('%s.project', $sprintAlias), $projectAlias)
      ->andWhere(sprintf('%s.owner = :current_user', $projectAlias));
  }
}
