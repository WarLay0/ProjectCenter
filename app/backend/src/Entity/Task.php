<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\TaskRepository;
use App\Trait\TimestampableTrait;
use App\Trait\UuidTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task', uniqueConstraints: [
  new ORM\UniqueConstraint(name: 'UNIQ_SPRINT_TASK_POSITION', columns: ['sprint_id', 'position'])
])]
#[ApiResource(
  operations: [new GetCollection(), new Get(), new Post(), new Patch(), new Delete()],
  normalizationContext: ['groups' => ['task:read']],
  denormalizationContext: ['groups' => ['task:write']]
)]
class Task
{
  // ==================== Traits ====================
  use UuidTrait;
  use TimestampableTrait;

  // ==================== Properties ====================

  #[ORM\Column(type: 'string', length: 255, nullable: false)]
  #[Assert\NotBlank(message: 'Le nom de la task est requis.')]
  #[Assert\Length(
    max: 255,
    maxMessage: 'Le nom de la task ne peut pas dépasser {{ limit }} caractères.'
  )]
  #[Groups(['task:read', 'task:write'])]
  private ?string $name = null;

  #[ORM\Column(type: 'text', nullable: true)]
  #[Assert\Length(
    max: 5000,
    maxMessage: 'La description de la task ne peut pas dépasser {{ limit }} caractères.'
  )]
  #[Groups(['task:read', 'task:write'])]
  private ?string $description = null;

  #[ORM\Column(type: 'string', length: 20, nullable: false)]
  #[Assert\NotBlank(message: 'Le statut de la task est requis.')]
  #[Assert\Choice(
    choices: ['todo', 'in_progress', 'done'],
    message: 'Le statut de la task doit être valide.'
  )]
  #[Groups(['task:read', 'task:write'])]
  private string $status = 'todo';

  #[ORM\Column(type: 'integer', nullable: false)]
  #[Assert\NotNull(message: 'La position de la task est requise.')]
  #[Assert\Positive(message: 'La position de la task doit être supérieure à 0.')]
  #[Groups(['task:read', 'task:write'])]
  private ?int $position = null;

  #[ORM\ManyToOne(inversedBy: 'tasks')]
  #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
  #[Assert\NotNull(message: 'Le sprint de la task est requis.')]
  #[ApiProperty(readableLink: false, writableLink: false)]
  #[Groups(['task:read', 'task:write'])]
  private ?Sprint $sprint = null;

  // ==================== Getters/Setters ====================

  public function getName(): ?string
  {
    return $this->name;
  }

  public function setName(string $name): self
  {
    $this->name = $name;

    return $this;
  }

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function setDescription(?string $description): self
  {
    $this->description = $description;

    return $this;
  }

  public function getStatus(): string
  {
    return $this->status;
  }

  public function setStatus(string $status): self
  {
    $this->status = $status;

    return $this;
  }

  public function getPosition(): ?int
  {
    return $this->position;
  }

  public function setPosition(int $position): self
  {
    $this->position = $position;

    return $this;
  }

  public function getSprint(): ?Sprint
  {
    return $this->sprint;
  }

  public function setSprint(?Sprint $sprint): self
  {
    $this->sprint = $sprint;

    return $this;
  }
}
