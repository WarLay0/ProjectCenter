<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use App\Entity\Project;
use App\Entity\Sprint;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

use function sprintf;

// On filtre les requetes Doctrine pour qu'un user ne voie que SES projets/sprints/taches.
// On l'applique aux GetCollection et aux Get item (transforme un 403 en 404 pour les ressources des autres).
// Pour les Patch/Delete, on laisse le security: de l'ApiResource decider (403 attendu par les tests).
final class OwnerFilterExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
  public function __construct(
    private Security $security,
    private RequestStack $requestStack,
  ) {
  }

  public function applyToCollection(
    QueryBuilder $queryBuilder,
    QueryNameGeneratorInterface $queryNameGenerator,
    string $resourceClass,
    ?Operation $operation = null,
    array $context = [],
  ): void {
    $this->filterByOwner($queryBuilder, $resourceClass);
  }

  public function applyToItem(
    QueryBuilder $queryBuilder,
    QueryNameGeneratorInterface $queryNameGenerator,
    string $resourceClass,
    array $identifiers,
    ?Operation $operation = null,
    array $context = [],
  ): void {
    if (!$operation instanceof Get) {
      return;
    }

    // Si l'item est resolu en interne (denormalisation d'un IRI dans un POST/PATCH),
    // on ne filtre pas : sinon le owner ne peut plus referencer ses propres ressources
    // et on bloque le securityPostDenormalize qui doit renvoyer 403 pour les autres users.
    if (!$this->isMainRequestForResource($resourceClass)) {
      return;
    }

    $this->filterByOwner($queryBuilder, $resourceClass);
  }

  private function isMainRequestForResource(string $resourceClass): bool
  {
    $request = $this->requestStack->getMainRequest();

    if ($request === null) {
      return false;
    }

    return $request->attributes->get('_api_resource_class') === $resourceClass;
  }

  private function filterByOwner(QueryBuilder $queryBuilder, string $resourceClass): void
  {
    $user = $this->security->getUser();

    if (!$user instanceof User) {
      return;
    }

    $rootAlias = $queryBuilder->getRootAliases()[0];

    $userId = $user->getId();

    if ($resourceClass === Project::class) {
      $queryBuilder
        ->andWhere(sprintf('IDENTITY(%s.owner) = :current_user_id', $rootAlias))
        ->setParameter('current_user_id', $userId, 'uuid');

      return;
    }

    if ($resourceClass === Sprint::class) {
      $queryBuilder
        ->join(sprintf('%s.project', $rootAlias), 'sp_project')
        ->andWhere('IDENTITY(sp_project.owner) = :current_user_id')
        ->setParameter('current_user_id', $userId, 'uuid');

      return;
    }

    if ($resourceClass === Task::class) {
      $queryBuilder
        ->join(sprintf('%s.sprint', $rootAlias), 't_sprint')
        ->join('t_sprint.project', 't_project')
        ->andWhere('IDENTITY(t_project.owner) = :current_user_id')
        ->setParameter('current_user_id', $userId, 'uuid');
    }
  }
}
