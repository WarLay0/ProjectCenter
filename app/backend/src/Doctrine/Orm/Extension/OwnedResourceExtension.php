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
use Symfony\Component\HttpFoundation\RequestStack;

use function in_array;
use function sprintf;

/**
 * Filtre automatiquement les collections pour ne retourner
 * que les ressources appartenant a l'utilisateur connecte.
 */
final class OwnedResourceExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
  // ==================== Constructor ====================

  public function __construct(
    private Security $security,
    private RequestStack $requestStack,
  ) {
  }

  // ==================== Interface methods ====================

  public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
  {
    $this->addOwnershipClause($queryBuilder, $queryNameGenerator, $resourceClass);
  }

  public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
  {
    $request = $this->requestStack->getCurrentRequest();

    // On laisse passer les PATCH/DELETE : la verification d'ownership
    // est faite par les expressions security sur l'entite.
    if ($request?->getMethod() !== 'GET') {
      return;
    }

    $this->addOwnershipClause($queryBuilder, $queryNameGenerator, $resourceClass);
  }

  // ==================== Helpers ====================

  private function addOwnershipClause(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass): void
  {
    if (!in_array($resourceClass, [Project::class, Sprint::class, Task::class], true)) {
      return;
    }

    $user = $this->security->getUser();

    if (!$user instanceof User) {
      // Pas authentifie -> on retourne aucun resultat
      $queryBuilder->andWhere('1 = 0');

      return;
    }

    $userId = $user->getId();

    if ($userId === null) {
      $queryBuilder->andWhere('1 = 0');

      return;
    }

    $rootAlias = $queryBuilder->getRootAliases()[0];

    match ($resourceClass) {
      Project::class => $this->addProjectOwnershipClause($queryBuilder, $queryNameGenerator, $rootAlias),
      Sprint::class => $this->addSprintOwnershipClause($queryBuilder, $queryNameGenerator, $rootAlias),
      Task::class => $this->addTaskOwnershipClause($queryBuilder, $queryNameGenerator, $rootAlias),
    };

    // Le type 'uuid' est important sinon Doctrine ne sait pas convertir l'objet Uuid
    $queryBuilder->setParameter('current_user_id', $userId, 'uuid');
  }

  private function addProjectOwnershipClause(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias): void
  {
    $ownerAlias = $queryNameGenerator->generateJoinAlias('owner');

    $queryBuilder
      ->innerJoin(sprintf('%s.owner', $rootAlias), $ownerAlias)
      ->andWhere(sprintf('%s.id = :current_user_id', $ownerAlias));
  }

  private function addSprintOwnershipClause(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias): void
  {
    $projectAlias = $queryNameGenerator->generateJoinAlias('project');
    $ownerAlias = $queryNameGenerator->generateJoinAlias('owner');

    $queryBuilder
      ->innerJoin(sprintf('%s.project', $rootAlias), $projectAlias)
      ->innerJoin(sprintf('%s.owner', $projectAlias), $ownerAlias)
      ->andWhere(sprintf('%s.id = :current_user_id', $ownerAlias));
  }

  private function addTaskOwnershipClause(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias): void
  {
    $sprintAlias = $queryNameGenerator->generateJoinAlias('sprint');
    $projectAlias = $queryNameGenerator->generateJoinAlias('project');
    $ownerAlias = $queryNameGenerator->generateJoinAlias('owner');

    $queryBuilder
      ->innerJoin(sprintf('%s.sprint', $rootAlias), $sprintAlias)
      ->innerJoin(sprintf('%s.project', $sprintAlias), $projectAlias)
      ->innerJoin(sprintf('%s.owner', $projectAlias), $ownerAlias)
      ->andWhere(sprintf('%s.id = :current_user_id', $ownerAlias));
  }
}
