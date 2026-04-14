<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Project;
use App\Entity\Sprint;
use App\Entity\Task;

final class OwnershipApiTest extends AbstractApiTestCase
{
  public function testUsersOnlyAccessTheirOwnResources(): void
  {
    $ownerClient = $this->createAuthenticatedClient('owner@example.com');

    $ownerClient->request('POST', '/api/projects', [
      'json' => [
        'name' => 'Owner Project',
        'description' => 'Project owned by the first user.',
      ],
    ]);

    self::assertResponseStatusCodeSame(201);

    $projectIri = $this->findIriBy(Project::class, ['name' => 'Owner Project']);

    self::assertNotNull($projectIri);

    $ownerClient->request('POST', '/api/sprints', [
      'json' => [
        'name' => 'Owner Sprint',
        'description' => 'Sprint owned by the first user.',
        'position' => 1,
        'project' => $projectIri,
      ],
    ]);

    self::assertResponseStatusCodeSame(201);

    $sprintIri = $this->findIriBy(Sprint::class, ['name' => 'Owner Sprint']);

    self::assertNotNull($sprintIri);

    $ownerClient->request('POST', '/api/tasks', [
      'json' => [
        'name' => 'Owner Task',
        'description' => 'Task owned by the first user.',
        'status' => 'todo',
        'position' => 1,
        'sprint' => $sprintIri,
      ],
    ]);

    self::assertResponseStatusCodeSame(201);

    $taskIri = $this->findIriBy(Task::class, ['name' => 'Owner Task']);

    self::assertNotNull($taskIri);

    $otherClient = $this->createAuthenticatedClient('other@example.com');

    $projectsResponse = $otherClient->request('GET', '/api/projects');

    self::assertResponseIsSuccessful();
    self::assertSame([], $projectsResponse->toArray(false));

    $sprintsResponse = $otherClient->request('GET', '/api/sprints');

    self::assertResponseIsSuccessful();
    self::assertSame([], $sprintsResponse->toArray(false));

    $tasksResponse = $otherClient->request('GET', '/api/tasks');

    self::assertResponseIsSuccessful();
    self::assertSame([], $tasksResponse->toArray(false));

    $otherClient->request('GET', $projectIri);
    self::assertResponseStatusCodeSame(404);

    $otherClient->request('GET', $sprintIri);
    self::assertResponseStatusCodeSame(404);

    $otherClient->request('GET', $taskIri);
    self::assertResponseStatusCodeSame(404);
  }
}
