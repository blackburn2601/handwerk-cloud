<?php

namespace App\Tests\Functional;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Rebuilds the test schema for every test so cases cannot leak into each other.
 */
abstract class DatabaseTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
    }

    /**
     * @param string[] $roles
     */
    protected function createUser(string $email, array $roles = ['ROLE_USER'], string $password = 'test1234'): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('Test');
        $user->setSurname('Benutzer');
        $user->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createCustomer(User $owner, string $surname = 'Berger'): Customer
    {
        $customer = new Customer();
        $customer->setTitle('Herr');
        $customer->setFirstname('Thomas');
        $customer->setSurname($surname);
        $customer->setStreet('Lindenweg');
        $customer->setHousenumber('14');
        $customer->setPlz('80331');
        $customer->setCity('München');
        $customer->setPhone('089 1234567');
        $customer->setCreatedBy($owner);

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }
}
