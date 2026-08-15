<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Offer;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Demo data so a fresh checkout has something to look at.
 *
 * Load with: bin/console doctrine:fixtures:load
 */
class AppFixtures extends Fixture
{
    public const DEMO_PASSWORD = 'demo1234';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->makeUser('admin@handwerkcloud.test', 'Anna', 'Admin', ['ROLE_ADMIN']);
        $fitter = $this->makeUser('monteur@handwerkcloud.test', 'Markus', 'Monteur', ['ROLE_USER']);

        $manager->persist($admin);
        $manager->persist($fitter);

        $customers = [
            ['Herr', 'Thomas', 'Berger', 'Lindenweg', '14', '80331', 'München', '089 1234567'],
            ['Frau', 'Sabine', 'Hoffmann', 'Am Mühlbach', '3a', '90402', 'Nürnberg', '0911 998877'],
            ['Herr', 'Jens', 'Kowalski', 'Industriestraße', '27', '04109', 'Leipzig', '0341 445566'],
            ['Frau', 'Petra', 'Wagner', 'Rosenstraße', '8', '70173', 'Stuttgart', '0711 223344'],
            ['Herr', 'Ali', 'Yilmaz', 'Hafenweg', '52', '20457', 'Hamburg', '040 776655'],
        ];

        foreach ($customers as $index => [$title, $firstname, $surname, $street, $number, $plz, $city, $phone]) {
            $owner = 0 === $index % 2 ? $admin : $fitter;

            $customer = new Customer();
            $customer->setTitle($title);
            $customer->setFirstname($firstname);
            $customer->setSurname($surname);
            $customer->setStreet($street);
            $customer->setHousenumber($number);
            $customer->setPlz($plz);
            $customer->setCity($city);
            $customer->setPhone($phone);
            $customer->setEmail(sprintf('%s.%s@example.test', strtolower($firstname), strtolower($surname)));
            $customer->setCreatedBy($owner);

            $manager->persist($customer);

            $offer = new Offer();
            $offer->setCustomer($customer);
            $offer->setCreatedBy($owner);
            $offer->setCreated(new \DateTime(sprintf('-%d days', 30 - $index * 3)));
            $offer->setOfferDate(new \DateTime(sprintf('-%d days', 28 - $index * 3)));
            $offer->setTermDate(new \DateTime(sprintf('+%d days', 7 + $index * 2)));
            $offer->setComment(sprintf('Angebot %d', 1000 + $index));
            $offer->setTextarea("Carport mit Solardach\nMontage inkl. Unterkonstruktion und Anschluss.");

            $manager->persist($offer);

            // The two oldest offers have already been turned into tasks.
            if ($index < 2) {
                $task = new Task();
                $task->setOffer($offer);
                $task->setCustomer($customer);
                $task->setCreatedBy($owner);
                $task->setTaskDate($offer->getTermDate());
                $task->setTermDate($offer->getTermDate());
                $task->setComment($offer->getComment());
                $task->setTextarea($offer->getTextarea());

                $manager->persist($task);
            }
        }

        $manager->flush();
    }

    /**
     * @param string[] $roles
     */
    private function makeUser(string $email, string $firstname, string $surname, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setSurname($surname);
        $user->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD));

        return $user;
    }
}
