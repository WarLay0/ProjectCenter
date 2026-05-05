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
use App\Repository\ProjectRepository;
use App\State\Processor\CreateProjectProcessor;
use App\Trait\TimestampableTrait;
use App\Trait\UuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Dto\CreateProjectInput;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ApiResource(
  operations: [
    new GetCollection(),
    new Get(security: 'object.getOwner() and user and object.getOwner().getId() == user.getId()'),
    new Post(input: CreateProjectInput::class, processor: CreateProjectProcessor::class),
    new Patch(security: 'object.getOwner() and user and object.getOwner().getId() == user.getId()'),
    new Delete(security: 'object.getOwner() and user and object.getOwner().getId() == user.getId()'),
  ],
  normalizationContext: ['groups' => ['project:read']],
  denormalizationContext: ['groups' => ['project:write']]
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['name', 'createdAt'])]
class Project
{
  // ==================== Traits ====================
  use UuidTrait;
  use TimestampableTrait;

  // ==================== Properties ====================

  #[ORM\Column(type: 'string', length: 255, nullable: false)]
  #[Assert\NotBlank(message: 'Le nom du projet est requis.')]
  #[Assert\Length(
    max: 255,
    maxMessage: 'Le nom du projet ne peut pas dépasser {{ limit }} caractères.'
  )]
  #[Groups(['project:read', 'project:write'])]
  private ?string $name = null;

  #[ORM\Column(type: 'text', nullable: true)]
  #[Assert\Length(
    max: 5000,
    maxMessage: 'La description du projet ne peut pas dépasser {{ limit }} caractères.'
  )]
  #[Groups(['project:read', 'project:write'])]
  private ?string $description = null;

  #[ORM\ManyToOne(inversedBy: 'projects')]
  #[ORM\JoinColumn(nullable: false)]
  #[ApiProperty(readableLink: false, writableLink: false)]
  #[Groups(['project:read'])]
  private ?User $owner = null;

  #[ORM\OneToMany(mappedBy: 'project', targetEntity: Sprint::class)]
  #[ApiProperty(readableLink: false, writableLink: false)]
  #[Groups(['project:read'])]
  private Collection $sprints;

  // ==================== Methods ====================

  public function __construct()
  {
    $this->sprints = new ArrayCollection();
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

  public function getOwner(): ?User
  {
    return $this->owner;
  }

  public function setOwner(?User $owner): self
  {
    $this->owner = $owner;

    return $this;
  }

  public function getSprints(): Collection
  {
    return $this->sprints;
  }

  public function addSprint(Sprint $sprint): self
  {
    if (!$this->sprints->contains($sprint)) {
      $this->sprints->add($sprint);
      $sprint->setProject($this);
    }

    return $this;
  }

  public function removeSprint(Sprint $sprint): self
  {
    if ($this->sprints->removeElement($sprint)) {
      if ($sprint->getProject() === $this) {
        $sprint->setProject(null);
      }
    }

    return $this;
  }
}
