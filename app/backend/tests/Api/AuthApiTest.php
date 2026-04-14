<?php

declare(strict_types=1);

namespace App\Tests\Api;

final class AuthApiTest extends AbstractApiTestCase
{
  public function testUserCanRegisterLoginAndAccessMe(): void
  {
    $email = 'alice@example.com';
    $password = 'password123';

    $this->client->request('POST', '/api/register', [
      'json' => [
        'email' => $email,
        'password' => $password,
      ],
    ]);

    self::assertResponseStatusCodeSame(201);

    $loginResponse = $this->client->request('POST', '/api/login_check', [
      'json' => [
        'email' => $email,
        'password' => $password,
      ],
    ]);

    self::assertResponseIsSuccessful();

    $data = $loginResponse->toArray(false);

    self::assertArrayHasKey('token', $data);
    self::assertIsString($data['token']);

    $authenticatedClient = static::createClient([], [
      'headers' => ['accept' => 'application/json'],
      'auth_bearer' => $data['token'],
    ]);

    $authenticatedClient->request('GET', '/api/me');

    self::assertResponseIsSuccessful();
    self::assertJsonContains(['email' => $email]);
  }

  public function testRegisterRejectsDuplicateEmail(): void
  {
    $this->registerUser('duplicate@example.com');

    $this->client->request('POST', '/api/register', [
      'json' => [
        'email' => 'duplicate@example.com',
        'password' => 'password123',
      ],
    ]);

    self::assertResponseStatusCodeSame(422);
  }

  public function testLoginRejectsInvalidPassword(): void
  {
    $this->registerUser('bob@example.com');

    $this->client->request('POST', '/api/login_check', [
      'json' => [
        'email' => 'bob@example.com',
        'password' => 'wrong-password',
      ],
    ]);

    self::assertResponseStatusCodeSame(401);
  }
}
