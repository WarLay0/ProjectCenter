<?php

declare(strict_types=1);

namespace App\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

trait UuidTrait
{
  #[ORM\Id]
  #[ORM\Column(type: 'uuid', unique: true)]
  #[ORM\GeneratedValue(strategy: 'NONE')]
  private ?Uuid $id = null;

  #[ORM\PrePersist]
  public function generateUuid(): void
  {
    if ($this->id === null) {
      $this->id = new UuidV7();
    }
  }

  public function getId(): ?Uuid
  {
    return $this->id;
  }

  public function getIdAsString(): ?string
  {
    return $this->id?->toRfc4122();
  }
}
