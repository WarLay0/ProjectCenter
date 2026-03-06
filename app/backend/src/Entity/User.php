<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\State\Provider\MeProvider;
use App\Dto\RegisterUserInput;
use App\Repository\UserRepository;
use App\State\Processor\RegisterUserProcessor;
use App\Trait\TimestampableTrait;
use App\Trait\UuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Cet email est deja utilise.')]
#[ApiResource(
  operations: [
    new Get(security: 'object == user'),
    new Get(
      uriTemplate: '/me',
      provider: MeProvider::class,
      security: "is_granted('IS_AUTHENTICATED_FULLY')"
    ),
    new Post(
      uriTemplate: '/register',
      input: RegisterUserInput::class,
      processor: RegisterUserProcessor::class
    ),
  ],
  normalizationContext: ['groups' => ['user:read']]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
  // ==================== Traits ====================
  use UuidTrait;
  use TimestampableTrait;

  // ==================== Properties ====================
  #[ORM\Column(length: 180)]
  #[Assert\NotBlank(message: 'L\'email est requis.')]
  #[Assert\Email(message: 'L\'email doit etre valide.')]
  #[Groups(['user:read'])]
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
