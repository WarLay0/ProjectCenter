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
use App\Repository\SprintRepository;
use App\State\Processor\CreateSprintProcessor;
use App\Trait\TimestampableTrait;
use App\Trait\UuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: SprintRepository::class)]
#[ORM\Table(name: 'sprint')]
#[ORM\UniqueConstraint(name: 'UNIQ_PROJECT_SPRINT_POSITION', fields: ['project', 'position'])]
#[ApiResource(
  operations: [
    new GetCollection(),
    new Get(security: 'object.getProject() and object.getProject().getOwner() and user and object.getProject().getOwner().getId() == user.getId()'),
    new Post(
      processor: CreateSprintProcessor::class,
      securityPostDenormalize: 'object.getProject() and object.getProject().getOwner() and user and object.getProject().getOwner().getId() == user.getId()'
    ),
    new Patch(
      security: 'object.getProject() and object.getProject().getOwner() and user and object.getProject().getOwner().getId() == user.getId()',
      securityPostDenormalize: 'object.getProject() and object.getProject().getOwner() and user and object.getProject().getOwner().getId() == user.getId()'
    ),
    new Delete(security: 'object.getProject() and object.getProject().getOwner() and user and object.getProject().getOwner().getId() == user.getId()'),
  ],
  normalizationContext: ['groups' => ['sprint:read']],
  denormalizationContext: ['groups' => ['sprint:write']]
)]
#[ApiFilter(SearchFilter::class, properties: ['project' => 'exact', 'name' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['position', 'createdAt'])]
class Sprint
{
  // ==================== Traits ====================
  use UuidTrait;
  use TimestampableTrait;

  // ==================== Properties ====================

  #[ORM\Column(type: 'string', length: 255, nullable: false)]
  #[Assert\NotBlank(message: 'Le nom du sprint est requis.')]
  #[Assert\Length(
    max: 255,
    maxMessage: 'Le nom du sprint ne peut pas dépasser {{ limit }} caractères.'
  )]
  #[Groups(['sprint:read', 'sprint:write'])]
  private ?string $name = null;

  #[ORM\Column(type: 'text', nullable: true)]
  #[Assert\Length(
    max: 5000,
    maxMessage: 'La description du sprint ne peut pas dépasser {{ limit }} caractères.'
  )]
  #[Groups(['sprint:read', 'sprint:write'])]
  private ?string $description = null;

  #[ORM\Column(type: 'integer', nullable: false)]
  #[Assert\NotNull(message: 'La position du sprint est requise.')]
  #[Assert\Positive(message: 'La position du sprint doit être supérieure à 0.')]
  #[Groups(['sprint:read', 'sprint:write'])]
  private ?int $position = null;

  #[ORM\ManyToOne(inversedBy: 'sprints')]
  #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
  #[Assert\NotNull(message: 'Le projet du sprint est requis.')]
  #[ApiProperty(readableLink: false, writableLink: false)]
  #[Groups(['sprint:read', 'sprint:write'])]
  private ?Project $project = null;

  #[ORM\OneToMany(mappedBy: 'sprint', targetEntity: Task::class)]
  #[ApiProperty(readableLink: false, writableLink: false)]
  #[Groups(['sprint:read'])]
  private Collection $tasks;

  // ==================== Methods ====================

  public function __construct()
  {
    $this->tasks = new ArrayCollection();
  }

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

  public function getPosition(): ?int
  {
    return $this->position;
  }

  public function setPosition(int $position): self
  {
    $this->position = $position;

    return $this;
  }

  public function getProject(): ?Project
  {
    return $this->project;
  }

  public function setProject(?Project $project): self
  {
    $this->project = $project;

    return $this;
  }

  public function getTasks(): Collection
  {
    return $this->tasks;
  }

  public function addTask(Task $task): self
  {
    if (!$this->tasks->contains($task)) {
      $this->tasks->add($task);
      $task->setSprint($this);
    }

    return $this;
  }

  public function removeTask(Task $task): self
  {
    if ($this->tasks->removeElement($task)) {
      if ($task->getSprint() === $this) {
        $task->setSprint(null);
      }
    }

    return $this;
  }
}
