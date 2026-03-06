<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectRepository;
use App\Trait\TimestampableTrait;
use App\Trait\UuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Validator\Constraints as Assert;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
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
  private ?string $name = null;

  #[ORM\Column(type: 'text', nullable: true)]
  #[Assert\Length(
    max: 5000,
    maxMessage: 'La description du projet ne peut pas dépasser {{ limit }} caractères.'
  )]
  private ?string $description = null;

  #[ORM\ManyToOne(inversedBy: 'projects')]
  #[ORM\JoinColumn(nullable: false)]
  private ?User $owner = null;

  #[ORM\OneToMany(mappedBy: 'project', targetEntity: Sprint::class)]
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
