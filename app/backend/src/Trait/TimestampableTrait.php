<?php

declare(strict_types=1);

namespace App\Trait;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait TimestampableTrait
{
  #[ORM\Column(type: 'datetime_immutable')]
  #[Groups(['user:read', 'project:read', 'sprint:read', 'task:read'])]
  private ?DateTimeImmutable $createdAt = null;

  #[ORM\Column(type: 'datetime_immutable')]
  #[Groups(['user:read', 'project:read', 'sprint:read', 'task:read'])]
  private ?DateTimeImmutable $updatedAt = null;

  #[ORM\PrePersist]
  public function initializeTimestamps(): void
  {
    $now = new DateTimeImmutable();

    $this->createdAt ??= $now;
    $this->updatedAt ??= $now;
  }

  #[ORM\PreUpdate]
  public function updateTimestamp(): void
  {
    $this->updatedAt = new DateTimeImmutable();
  }

  public function getCreatedAt(): ?DateTimeImmutable
  {
    return $this->createdAt;
  }

  public function getUpdatedAt(): ?DateTimeImmutable
  {
    return $this->updatedAt;
  }
}
