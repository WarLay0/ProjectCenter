<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Dto\RegisterUserInput;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegisterUserProcessor implements ProcessorInterface
{
  public function __construct(
    #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
    private ProcessorInterface $persistProcessor,
    private UserPasswordHasherInterface $userPasswordHasher,
    private ValidatorInterface $validator,
  ) {
  }

  public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
  {
    if (!$data instanceof RegisterUserInput) {
      throw new \InvalidArgumentException('Invalid register payload.');
    }

    $user = new User();
    $user->setEmail($data->email ?? '');
    $user->setRoles(['ROLE_USER']);
    $user->setPassword($this->userPasswordHasher->hashPassword($user, $data->password ?? ''));

    $violations = $this->validator->validate($user);

    if (count($violations) > 0) {
      throw new ValidationException($violations);
    }

    return $this->persistProcessor->process($user, $operation, $uriVariables, $context);
  }
}
