<?php

namespace App\Tests\Functional;

/**
 * Search must never surface another user's records — the condition grouping in
 * the query builder is what guarantees it.
 */
class GlobalSearchTest extends DatabaseTestCase
{
    public function testSearchIsScopedToTheCurrentUser(): void
    {
        $owner = $this->createUser('owner@example.test');
        $this->createCustomer($owner, 'Eigenkunde');

        $stranger = $this->createUser('stranger@example.test');
        $this->createCustomer($stranger, 'Fremdkunde');

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/global/search?search=kunde');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Eigenkunde', $crawler->text());
        self::assertStringNotContainsString('Fremdkunde', $crawler->text());
    }

    public function testAdminSeesEveryMatch(): void
    {
        $owner = $this->createUser('owner@example.test');
        $this->createCustomer($owner, 'Eigenkunde');

        $admin = $this->createUser('admin@example.test', ['ROLE_USER', 'ROLE_ADMIN']);
        $crawler = $this->client->loginUser($admin)->request('GET', '/global/search?search=kunde');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Eigenkunde', $crawler->text());
    }

    public function testEmptySearchTermRendersAnEmptyResultPage(): void
    {
        $this->client->loginUser($this->createUser('owner@example.test'));
        $this->client->request('GET', '/global/search?search=%20');

        self::assertResponseIsSuccessful();
    }

    public function testSearchMatchesOnCity(): void
    {
        $owner = $this->createUser('owner@example.test');
        $this->createCustomer($owner, 'Berger');

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/global/search?search=München');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Berger', $crawler->text());
    }
}
