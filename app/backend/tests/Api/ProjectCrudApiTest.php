<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Project;

final class ProjectCrudApiTest extends AbstractApiTestCase
{
  // ==================== Création ====================

  public function testCreateProjectReturns201(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');

    $client->request('POST', '/api/projects', [
      'json' => [
        'name' => 'My Project',
        'description' => 'A short description.',
      ],
    ]);

    self::assertResponseStatusCodeSame(201);
    self::assertJsonContains(['name' => 'My Project']);
  }

  public function testCreateProjectRejectsBlankName(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');

    $client->request('POST', '/api/projects', [
      'json' => [
        'description' => 'No name provided.',
      ],
    ]);

    self::assertResponseStatusCodeSame(422);
  }

  public function testCreateProjectRejectsTooLongName(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');

    $client->request('POST', '/api/projects', [
      'json' => [
        'name' => str_repeat('a', 256),
      ],
    ]);

    self::assertResponseStatusCodeSame(422);
  }

  public function testCreateProjectRequiresAuthentication(): void
  {
    $this->client->request('POST', '/api/projects', [
      'json' => ['name' => 'Anonymous Project'],
    ]);

    self::assertResponseStatusCodeSame(401);
  }

  // ==================== Lecture ====================

  public function testGetCollectionReturnsOnlyOwnProjects(): void
  {
    $owner = $this->createAuthenticatedClient('owner@example.com');

    $owner->request('POST', '/api/projects', ['json' => ['name' => 'Owner P']]);
    self::assertResponseStatusCodeSame(201);

    $ownerResponse = $owner->request('GET', '/api/projects');
    self::assertResponseIsSuccessful();
    $ownerData = $ownerResponse->toArray(false);
    self::assertCount(1, $ownerData);
    self::assertSame('Owner P', $ownerData[0]['name']);

    $other = $this->createAuthenticatedClient('other@example.com');

    $otherResponse = $other->request('GET', '/api/projects');
    self::assertResponseIsSuccessful();
    self::assertSame([], $otherResponse->toArray(false));
  }

  // ==================== Mise à jour ====================

  public function testUpdateProjectAsOwnerReturns200(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');

    $client->request('POST', '/api/projects', [
      'json' => ['name' => 'Original'],
    ]);
    self::assertResponseStatusCodeSame(201);

    $iri = $this->findIriBy(Project::class, ['name' => 'Original']);
    self::assertNotNull($iri);

    $client->request('PATCH', $iri, [
      'headers' => ['Content-Type' => 'application/merge-patch+json'],
      'json' => ['name' => 'Renamed'],
    ]);

    self::assertResponseIsSuccessful();
    self::assertJsonContains(['name' => 'Renamed']);
  }

  public function testUpdateProjectByOtherUserReturns404(): void
  {
    $owner = $this->createAuthenticatedClient('owner@example.com');

    $owner->request('POST', '/api/projects', ['json' => ['name' => 'Owner Only']]);
    self::assertResponseStatusCodeSame(201);

    $iri = $this->findIriBy(Project::class, ['name' => 'Owner Only']);
    self::assertNotNull($iri);

    $other = $this->createAuthenticatedClient('other@example.com');

    $other->request('PATCH', $iri, [
      'headers' => ['Content-Type' => 'application/merge-patch+json'],
      'json' => ['name' => 'Hijacked'],
    ]);

    self::assertResponseStatusCodeSame(403);
  }

  // ==================== Suppression ====================

  public function testDeleteProjectAsOwnerReturns204(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');

    $client->request('POST', '/api/projects', ['json' => ['name' => 'To delete']]);
    self::assertResponseStatusCodeSame(201);

    $iri = $this->findIriBy(Project::class, ['name' => 'To delete']);
    self::assertNotNull($iri);

    $client->request('DELETE', $iri);
    self::assertResponseStatusCodeSame(204);

    $client->request('GET', $iri);
    self::assertResponseStatusCodeSame(404);
  }

  public function testDeleteProjectByOtherUserReturns404(): void
  {
    $owner = $this->createAuthenticatedClient('owner@example.com');

    $owner->request('POST', '/api/projects', ['json' => ['name' => 'Protected']]);
    self::assertResponseStatusCodeSame(201);

    $iri = $this->findIriBy(Project::class, ['name' => 'Protected']);
    self::assertNotNull($iri);

    $other = $this->createAuthenticatedClient('other@example.com');

    $other->request('DELETE', $iri);
    self::assertResponseStatusCodeSame(403);
  }
}
