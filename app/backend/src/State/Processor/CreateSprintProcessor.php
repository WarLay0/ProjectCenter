<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\Sprint;
use App\Entity\User;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function count;

final class CreateSprintProcessor implements ProcessorInterface
{
  public function __construct(
    #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
    private ProcessorInterface $persistProcessor,
    private Security $security,
    private ValidatorInterface $validator,
  ) {
  }

  public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Sprint
  {
    if (!$data instanceof Sprint) {
      throw new InvalidArgumentException('Invalid sprint payload.');
    }

    $user = $this->security->getUser();

    if (!$user instanceof User) {
      throw new AccessDeniedHttpException('Authentication required.');
    }

    // On verifie que le projet appartient bien a l'utilisateur connecte
    $project = $data->getProject();

    if ($project === null || $project->getOwner() !== $user) {
      throw new AccessDeniedHttpException('You cannot create a sprint in this project.');
    }

    // TODO: gerer le cas ou la position est deja prise (actuellement ca part en 500 a cause de la contrainte unique)

    $violations = $this->validator->validate($data);

    if (count($violations) > 0) {
      throw new ValidationException($violations);
    }

    return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
  }
}
