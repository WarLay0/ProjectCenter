<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class MeProvider implements ProviderInterface
{
  public function __construct(
    private Security $security,
  ) {
  }

  public function provide(Operation $operation, array $uriVariables = [], array $context = []): User
  {
    $user = $this->security->getUser();

    if (!$user instanceof User) {
      throw new AccessDeniedHttpException('Authentication required.');
    }

    return $user;
  }
}
