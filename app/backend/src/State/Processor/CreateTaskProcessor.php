<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateTaskProcessor implements ProcessorInterface
{
  public function __construct(
    #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
    private ProcessorInterface $persistProcessor,
    private Security $security,
    private ValidatorInterface $validator,
  ) {
  }

  public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Task
  {
    if (!$data instanceof Task) {
      throw new \InvalidArgumentException('Invalid task payload.');
    }

    $user = $this->security->getUser();

    if (!$user instanceof User) {
      throw new AccessDeniedHttpException('Authentication required.');
    }

    $sprint = $data->getSprint();

    if ($sprint === null || $sprint->getProject() === null || $sprint->getProject()->getOwner() !== $user) {
      throw new AccessDeniedHttpException('You cannot create a task in this sprint.');
    }

    $violations = $this->validator->validate($data);

    if (count($violations) > 0) {
      throw new ValidationException($violations);
    }

    return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
  }
}
