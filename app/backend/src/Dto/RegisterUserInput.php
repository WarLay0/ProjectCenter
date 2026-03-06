<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserInput
{
  #[Assert\NotBlank(message: 'L\'email est requis.')]
  #[Assert\Email(message: 'L\'email doit etre valide.')]
  public ?string $email = null;

  #[Assert\NotBlank(message: 'Le mot de passe est requis.')]
  #[Assert\Length(
    min: 8,
    minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caracteres.'
  )]
  public ?string $password = null;
}
