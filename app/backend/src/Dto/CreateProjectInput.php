<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CreateProjectInput
{
  #[Groups(['project:write'])]
  #[Assert\NotBlank(message: 'Le nom du projet est requis.')]
  #[Assert\Length(
    max: 255,
    maxMessage: 'Le nom du projet ne peut pas dépasser {{ limit }} caractères.'
  )]
  public ?string $name = null;

  #[Groups(['project:write'])]
  #[Assert\Length(
    max: 5000,
    maxMessage: 'La description du projet ne peut pas dépasser {{ limit }} caractères.'
  )]
  public ?string $description = null;
}