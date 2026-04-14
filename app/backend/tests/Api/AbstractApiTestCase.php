<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase as BaseApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

abstract class AbstractApiTestCase extends BaseApiTestCase
{
  protected static ?bool $alwaysBootKernel = true;

  protected Client $client;

  private ?EntityManagerInterface $entityManager = null;

  protected function setUp(): void
  {
    parent::setUp();

    static::ensureKernelShutdown();

    $this->client = static::createClient([], [
      'headers' => ['accept' => 'application/json'],
    ]);

    $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    $this->resetDatabase();
  }

  protected function tearDown(): void
  {
    if ($this->entityManager instanceof EntityManagerInterface) {
      $this->entityManager->clear();
      $this->entityManager->getConnection()->close();
    }

    $this->entityManager = null;

    parent::tearDown();
  }

  protected function registerUser(string $email, string $password = 'password123'): void
  {
    $this->client->request('POST', '/api/register', [
      'json' => [
        'email' => $email,
        'password' => $password,
      ],
    ]);

    self::assertResponseStatusCodeSame(201);
  }

  protected function loginUser(string $email, string $password = 'password123'): string
  {
    $response = $this->client->request('POST', '/api/login_check', [
      'json' => [
        'email' => $email,
        'password' => $password,
      ],
    ]);

    self::assertResponseIsSuccessful();

    $data = $response->toArray(false);

    self::assertArrayHasKey('token', $data);

    return $data['token'];
  }

  protected function createAuthenticatedClient(string $email, string $password = 'password123'): Client
  {
    $this->registerUser($email, $password);

    return static::createClient([], [
      'headers' => ['accept' => 'application/json'],
      'auth_bearer' => $this->loginUser($email, $password),
    ]);
  }

  private function resetDatabase(): void
  {
    if (!$this->entityManager instanceof EntityManagerInterface) {
      return;
    }

    $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

    if ($metadata === []) {
      return;
    }

    $schemaTool = new SchemaTool($this->entityManager);

    try {
      $schemaTool->dropSchema($metadata);
    } catch (\Throwable) {
    }

    $schemaTool->createSchema($metadata);
  }
}
