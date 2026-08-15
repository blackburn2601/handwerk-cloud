<?php

namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

/**
 * The record-level rules that the EntityOwnerVoter enforces, exercised through
 * real requests.
 */
class AuthorizationTest extends DatabaseTestCase
{
    public function testAnonymousVisitorIsSentToLogin(): void
    {
        $this->client->request('GET', '/customer/');

        self::assertResponseRedirects('/login');
    }

    public function testLoginPageIsPublic(): void
    {
        $crawler = $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'HandwerkCloud');
        self::assertCount(1, $crawler->filter('input[name="password"]'));
        // A session-backed CSRF token, so the form works without JavaScript.
        self::assertCount(1, $crawler->filter('input[name="_csrf_token"]'));
    }

    /**
     * Drives the real login form rather than the loginUser() shortcut, so the
     * CSRF token and authenticator are actually exercised.
     */
    public function testUserCanLogInThroughTheForm(): void
    {
        $this->createUser('fitter@example.test', ['ROLE_USER'], 'test1234');

        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->selectButton('Anmelden')->form([
            'email' => 'fitter@example.test',
            'password' => 'test1234',
        ]));

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testLoginFailsWithAWrongPassword(): void
    {
        $this->createUser('fitter@example.test', ['ROLE_USER'], 'test1234');

        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->selectButton('Anmelden')->form([
            'email' => 'fitter@example.test',
            'password' => 'wrong-password',
        ]));

        self::assertResponseRedirects('/login');
        $crawler = $this->client->followRedirect();
        // Framework message, translated because default_locale is de.
        self::assertStringContainsString('Fehlerhafte Zugangsdaten', $crawler->text());
    }

    public function testUserCannotEditAnotherUsersCustomer(): void
    {
        $owner = $this->createUser('owner@example.test');
        $customer = $this->createCustomer($owner);
        $stranger = $this->createUser('stranger@example.test');

        $this->client->loginUser($stranger);
        $this->client->request('GET', sprintf('/customer/%d/edit', $customer->getId()));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUserCanEditOwnCustomer(): void
    {
        $owner = $this->createUser('owner@example.test');
        $customer = $this->createCustomer($owner);

        $this->client->loginUser($owner);
        $this->client->request('GET', sprintf('/customer/%d/edit', $customer->getId()));

        self::assertResponseIsSuccessful();
    }

    public function testAdminCanEditAnyCustomer(): void
    {
        $owner = $this->createUser('owner@example.test');
        $customer = $this->createCustomer($owner);
        $admin = $this->createUser('admin@example.test', ['ROLE_USER', 'ROLE_ADMIN']);

        $this->client->loginUser($admin);
        $this->client->request('GET', sprintf('/customer/%d/edit', $customer->getId()));

        self::assertResponseIsSuccessful();
    }

    public function testUserAdministrationIsAdminOnly(): void
    {
        $this->client->loginUser($this->createUser('plain@example.test'));
        $this->client->request('GET', '/user/');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCustomerListOnlyShowsOwnRecords(): void
    {
        $owner = $this->createUser('owner@example.test');
        $this->createCustomer($owner, 'Sichtbar');

        $stranger = $this->createUser('stranger@example.test');
        $this->createCustomer($stranger, 'Fremd');

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/customer/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Sichtbar', $crawler->text());
        self::assertStringNotContainsString('Fremd', $crawler->text());
    }
}
