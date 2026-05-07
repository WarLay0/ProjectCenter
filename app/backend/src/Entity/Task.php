<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\TaskRepository;
use App\State\Processor\CreateTaskProcessor;
use App\Trait\TimestampableTrait;
use App\Trait\UuidTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
#[ORM\UniqueConstraint(name: 'UNIQ_SPRINT_TASK_POSITION', fields: ['sprint', 'position'])]
#[ApiResource(
  operations: [
    new GetCollection(),
    new Get(
      security: 'object.getSprint() and object.getSprint().getProject() and object.getSprint().getProject().getOwner() and user and object.getSprint().getProject().getOwner().getId() == user.getId()'
    ),
    new Post(
      processor: CreateTaskProcessor::class,
      securityPostDenormalize: 'object.getSprint() and object.getSprint().getProject() and object.getSprint().getProject().getOwner() and user and object.getSprint().getProject().getOwner().getId() == user.getId()'
    ),
    new Patch(
      security: 'object.getSprint() and object.getSprint().getProject() and object.getSprint().getProject().getOwner() and user and object.getSprint().getProject().getOwner().getId() == user.getId()',
      securityPostDenormalize: 'object.getSprint() and object.getSprint().getProject() and object.getSprint().getProject().getOwner() and user and object.getSprint().getProject().getOwner().getId() == user.getId()'
    ),
    new Delete(
      security: 'object.getSprint() and object.getSprint().getProject() and object.getSprint().getProject().getOwner() and user and object.getSprint().getProject().getOwner().getId() == user.getId()'
    ),
  ],
  normalizationContext: ['groups' => ['task:read']],
  denormalizationContext: ['groups' => ['task:write']]
)]
#[ApiFilter(SearchFilter::class, properties: [
  'sprint' => 'exact',
  'status' => 'exact',
  'name' => 'partial',
  'assignee' => 'partial',
])]
#[ApiFilter(OrderFilter::class, properties: ['position', 'createdAt'])]
class Task
{
  use UuidTrait;
  use TimestampableTrait;

  #[ORM\Column(type: 'string', length: 255, nullable: false)]
  #[Assert\NotBlank(message: 'Le nom de la tâche est requis.')]
  #[Assert\Length(
    max: 255,
    maxMessage: 'Le nom de la tâche ne peut pas dépasser {{ limit }} caractères.'
  )]
  #[Groups(['task:read', 'task:write'])]
  private ?string $name = null;

  #[ORM\Column(type: 'text', nullable: true)]
  #[Assert\Length(
    max: 5000,
    maxMessage: 'La description de la tâche ne peut pas dépasser {{ limit }} caractères.'
  )]
  #[Groups(['task:read', 'task:write'])]
  private ?string $description = null;

  #[ORM\Column(type: 'string', length: 20, nullable: false)]
  #[Assert\NotBlank(message: 'Le statut de la tâche est requis.')]
  #[Assert\Choice(
    choices: ['todo', 'in_progress', 'done'],
    message: 'Le statut de la tâche doit être valide.'
  )]
  #[Groups(['task:read', 'task:write'])]
  private string $status = 'todo';

  #[ORM\Column(type: 'integer', nullable: false)]
  #[Assert\NotNull(message: 'La position de la tâche est requise.')]
  #[Assert\Positive(message: 'La position de la tâche doit être supérieure à 0.')]
  #[Groups(['task:read', 'task:write'])]
  private ?int $position = null;

  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  #[Groups(['task:read', 'task:write'])]
  private ?string $assignee = null;

  #[ORM\ManyToOne(inversedBy: 'tasks')]
  #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
  #[Assert\NotNull(message: 'Le sprint de la tâche est requis.')]
  #[ApiProperty(readableLink: false, writableLink: false)]
  #[Groups(['task:read', 'task:write'])]
  private ?Sprint $sprint = null;

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

  public function getAssignee(): ?string
  {
    return $this->assignee;
  }

  public function setAssignee(?string $assignee): self
  {
    $this->assignee = $assignee;

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
