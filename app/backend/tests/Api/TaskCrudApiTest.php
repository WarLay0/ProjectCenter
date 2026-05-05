<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Project;
use App\Entity\Sprint;
use App\Entity\Task;

final class TaskCrudApiTest extends AbstractApiTestCase
{
  // ==================== Création ====================

  public function testCreateTaskReturns201(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $sprintIri = $this->createSprint($client);

    $client->request('POST', '/api/tasks', [
      'json' => [
        'name' => 'Task 1',
        'description' => 'First task.',
        'status' => 'todo',
        'position' => 1,
        'sprint' => $sprintIri,
      ],
    ]);

    self::assertResponseStatusCodeSame(201);
    self::assertJsonContains(['name' => 'Task 1', 'status' => 'todo']);
  }

  public function testCreateTaskRejectsBlankName(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $sprintIri = $this->createSprint($client);

    $client->request('POST', '/api/tasks', [
      'json' => [
        'status' => 'todo',
        'position' => 1,
        'sprint' => $sprintIri,
      ],
    ]);

    self::assertResponseStatusCodeSame(422);
  }

  public function testCreateTaskRejectsInvalidStatus(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $sprintIri = $this->createSprint($client);

    $client->request('POST', '/api/tasks', [
      'json' => [
        'name' => 'Bad status',
        'status' => 'invalid_status',
        'position' => 1,
        'sprint' => $sprintIri,
      ],
    ]);

    self::assertResponseStatusCodeSame(422);
  }

  public function testCreateTaskInOtherUserSprintIsForbidden(): void
  {
    $owner = $this->createAuthenticatedClient('owner@example.com');
    $sprintIri = $this->createSprint($owner);

    $other = $this->createAuthenticatedClient('other@example.com');

    $other->request('POST', '/api/tasks', [
      'json' => [
        'name' => 'Hijacked',
        'status' => 'todo',
        'position' => 1,
        'sprint' => $sprintIri,
      ],
    ]);

    self::assertResponseStatusCodeSame(403);
  }

  // ==================== Mise à jour ====================

  public function testUpdateTaskStatusAsOwnerReturns200(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $sprintIri = $this->createSprint($client);

    $client->request('POST', '/api/tasks', [
      'json' => [
        'name' => 'Original',
        'status' => 'todo',
        'position' => 1,
        'sprint' => $sprintIri,
      ],
    ]);
    self::assertResponseStatusCodeSame(201);

    $iri = $this->findIriBy(Task::class, ['name' => 'Original']);
    self::assertNotNull($iri);

    $client->request('PATCH', $iri, [
      'headers' => ['Content-Type' => 'application/merge-patch+json'],
      'json' => ['status' => 'in_progress'],
    ]);

    self::assertResponseIsSuccessful();
    self::assertJsonContains(['status' => 'in_progress']);
  }

  // ==================== Filtres ====================

  public function testFilterTasksByStatusReturnsOnlyMatching(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $sprintIri = $this->createSprint($client);

    foreach (['todo' => 'T1', 'in_progress' => 'T2', 'done' => 'T3'] as $status => $name) {
      $position = ['todo' => 1, 'in_progress' => 2, 'done' => 3][$status];

      $client->request('POST', '/api/tasks', [
        'json' => ['name' => $name, 'status' => $status, 'position' => $position, 'sprint' => $sprintIri],
      ]);
      self::assertResponseStatusCodeSame(201);
    }

    $allResponse = $client->request('GET', '/api/tasks');
    self::assertResponseIsSuccessful();
    self::assertCount(3, $allResponse->toArray(false));

    $response = $client->request('GET', '/api/tasks?status=done');
    self::assertResponseIsSuccessful();
    $data = $response->toArray(false);
    self::assertCount(1, $data);
    self::assertSame('done', $data[0]['status']);
  }

  // ==================== Suppression ====================

  public function testDeleteTaskAsOwnerReturns204(): void
  {
    $client = $this->createAuthenticatedClient('owner@example.com');
    $sprintIri = $this->createSprint($client);

    $client->request('POST', '/api/tasks', [
      'json' => ['name' => 'To delete', 'status' => 'todo', 'position' => 1, 'sprint' => $sprintIri],
    ]);
    self::assertResponseStatusCodeSame(201);

    $iri = $this->findIriBy(Task::class, ['name' => 'To delete']);
    self::assertNotNull($iri);

    $client->request('DELETE', $iri);
    self::assertResponseStatusCodeSame(204);

    $client->request('GET', $iri);
    self::assertResponseStatusCodeSame(404);
  }

  // ==================== Helpers ====================

  private function createSprint(Client $client): string
  {
    $client->request('POST', '/api/projects', ['json' => ['name' => 'Project A']]);
    self::assertResponseStatusCodeSame(201);
    $projectIri = $this->findIriBy(Project::class, ['name' => 'Project A']);
    self::assertNotNull($projectIri);

    $client->request('POST', '/api/sprints', [
      'json' => ['name' => 'Sprint A', 'position' => 1, 'project' => $projectIri],
    ]);
    self::assertResponseStatusCodeSame(201);
    $sprintIri = $this->findIriBy(Sprint::class, ['name' => 'Sprint A']);
    self::assertNotNull($sprintIri);

    return $sprintIri;
  }
}
