<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use App\Trait\TimestampableTrait;
use App\Trait\UuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
  // ==================== Traits ====================
  use UuidTrait;
  use TimestampableTrait;

  // ==================== Properties ====================
  #[ORM\Column(length: 180)]
  private ?string $email = null;

  #[ORM\Column]
  private array $roles = [];

  #[ORM\Column]
  private ?string $password = null;

  #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Project::class)]
  private Collection $projects;

  // ==================== Methods ====================
  public function __construct()
  {
    $this->projects = new ArrayCollection();
  }

  // ==================== Getters/Setters ====================
  public function getEmail(): ?string
  {
    return $this->email;
  }

  public function setEmail(string $email): static
  {
    $this->email = $email;

    return $this;
  }

  public function getUserIdentifier(): string
  {
    return (string) $this->email;
  }

  public function getRoles(): array
  {
    $roles = $this->roles;
    // guarantee every user at least has ROLE_USER
    $roles[] = 'ROLE_USER';

    return array_unique($roles);
  }

  public function setRoles(array $roles): static
  {
    $this->roles = $roles;

    return $this;
  }

  public function getPassword(): ?string
  {
    return $this->password;
  }

  public function setPassword(string $password): static
  {
    $this->password = $password;

    return $this;
  }

  public function getProjects(): Collection
  {
    return $this->projects;
  }

  public function addProject(Project $project): static
  {
    if (!$this->projects->contains($project)) {
      $this->projects->add($project);
      $project->setOwner($this);
    }

    return $this;
  }

  public function removeProject(Project $project): static
  {
    if ($this->projects->removeElement($project)) {
      if ($project->getOwner() === $this) {
        $project->setOwner(null);
      }
    }

    return $this;
  }

  public function __serialize(): array
  {
    $data = (array) $this;
    $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

    return $data;
  }
}
