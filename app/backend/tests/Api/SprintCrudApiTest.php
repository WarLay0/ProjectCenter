<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Project;
use App\Entity\Sprint;

final class SprintCrudApiTest extends AbstractApiTestCase
{
  // ==================== Création ====================

  public function testCreateSprintReturns201(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $projectIri = $this->createProject($client, 'Project A');

    $client->request('POST', '/api/sprints', [
      'json' => [
        'name' => 'Sprint 1',
        'description' => 'First sprint.',
        'position' => 1,
        'project' => $projectIri,
      ],
    ]);

    self::assertResponseStatusCodeSame(201);
    self::assertJsonContains(['name' => 'Sprint 1', 'position' => 1]);
  }

  public function testCreateSprintRejectsBlankName(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $projectIri = $this->createProject($client, 'Project A');

    $client->request('POST', '/api/sprints', [
      'json' => [
        'position' => 1,
        'project' => $projectIri,
      ],
    ]);

    self::assertResponseStatusCodeSame(422);
  }

  public function testCreateSprintRejectsNegativePosition(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $projectIri = $this->createProject($client, 'Project A');

    $client->request('POST', '/api/sprints', [
      'json' => [
        'name' => 'Sprint X',
        'position' => -1,
        'project' => $projectIri,
      ],
    ]);

    self::assertResponseStatusCodeSame(422);
  }

  public function testCreateSprintRejectsDuplicatePositionInSameProject(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $projectIri = $this->createProject($client, 'Project A');

    $client->request('POST', '/api/sprints', [
      'json' => ['name' => 'Sprint 1', 'position' => 1, 'project' => $projectIri],
    ]);
    self::assertResponseStatusCodeSame(201);

    $client->request('POST', '/api/sprints', [
      'json' => ['name' => 'Sprint 1bis', 'position' => 1, 'project' => $projectIri],
    ]);

    self::assertResponseStatusCodeSame(500);
  }

  public function testCreateSprintInOtherUserProjectIsForbidden(): void
  {
    $owner = $this->createAuthenticatedClient('owner@example.com');
    $projectIri = $this->createProject($owner, 'Owner Project');

    $other = $this->createAuthenticatedClient('other@example.com');

    $other->request('POST', '/api/sprints', [
      'json' => [
        'name' => 'Hijacked Sprint',
        'position' => 1,
        'project' => $projectIri,
      ],
    ]);

    self::assertResponseStatusCodeSame(403);
  }

  // ==================== Mise à jour ====================

  public function testUpdateSprintAsOwnerReturns200(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $projectIri = $this->createProject($client, 'Project A');

    $client->request('POST', '/api/sprints', [
      'json' => ['name' => 'Original', 'position' => 1, 'project' => $projectIri],
    ]);
    self::assertResponseStatusCodeSame(201);

    $iri = $this->findIriBy(Sprint::class, ['name' => 'Original']);
    self::assertNotNull($iri);

    $client->request('PATCH', $iri, [
      'headers' => ['Content-Type' => 'application/merge-patch+json'],
      'json' => ['name' => 'Renamed Sprint'],
    ]);

    self::assertResponseIsSuccessful();
    self::assertJsonContains(['name' => 'Renamed Sprint']);
  }

  // ==================== Suppression ====================

  public function testDeleteSprintAsOwnerReturns204(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $projectIri = $this->createProject($client, 'Project A');

    $client->request('POST', '/api/sprints', [
      'json' => ['name' => 'To delete', 'position' => 1, 'project' => $projectIri],
    ]);
    self::assertResponseStatusCodeSame(201);

    $iri = $this->findIriBy(Sprint::class, ['name' => 'To delete']);
    self::assertNotNull($iri);

    $client->request('DELETE', $iri);
    self::assertResponseStatusCodeSame(204);

    $client->request('GET', $iri);
    self::assertResponseStatusCodeSame(404);
  }

  public function testDeleteSprintByOtherUserReturns403(): void
  {
    $owner = $this->createAuthenticatedClient('owner@example.com');
    $projectIri = $this->createProject($owner, 'Owner Project');

    $owner->request('POST', '/api/sprints', [
      'json' => ['name' => 'Protected', 'position' => 1, 'project' => $projectIri],
    ]);
    self::assertResponseStatusCodeSame(201);

    $iri = $this->findIriBy(Sprint::class, ['name' => 'Protected']);
    self::assertNotNull($iri);

    $other = $this->createAuthenticatedClient('other@example.com');

    $other->request('DELETE', $iri);
    self::assertResponseStatusCodeSame(403);
  }

  // ==================== Helpers ====================

  private function createProject(Client $client, string $name): string
  {
    $client->request('POST', '/api/projects', [
      'json' => ['name' => $name],
    ]);
    self::assertResponseStatusCodeSame(201);

    $iri = $this->findIriBy(Project::class, ['name' => $name]);
    self::assertNotNull($iri);

    return $iri;
  }
}
