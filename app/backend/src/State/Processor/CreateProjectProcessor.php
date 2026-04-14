<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Dto\CreateProjectInput;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateProjectProcessor implements ProcessorInterface
{
  public function __construct(
    #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
    private ProcessorInterface $persistProcessor,
    private Security $security,
    private ValidatorInterface $validator,
  ) {
  }

  public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Project
  {
    if (!$data instanceof CreateProjectInput) {
      throw new \InvalidArgumentException('Invalid project payload.');
    }

    $user = $this->security->getUser();

    if (!$user instanceof User) {
      throw new AccessDeniedHttpException('Authentication required.');
    }

    $project = new Project();
    $project->setName($data->name ?? '');
    $project->setDescription($data->description);
    $project->setOwner($user);

    $violations = $this->validator->validate($project);

    if (count($violations) > 0) {
      throw new ValidationException($violations);
    }

    return $this->persistProcessor->process($project, $operation, $uriVariables, $context);
  }
}
