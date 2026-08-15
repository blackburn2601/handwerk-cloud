<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Offer;
use App\Entity\Task;
use App\Entity\TaskDraw;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Demo data so a fresh checkout has something to look at.
 *
 * Load with: bin/console doctrine:fixtures:load
 */
class AppFixtures extends Fixture
{
    public const DEMO_PASSWORD = 'demo1234';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Filesystem $filesystem,
        private readonly string $taskDrawingsDir,
    ) {
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
            $task = null;
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

            // Give the first two offers a sketch, so the drawing feature is
            // visible immediately after loading the fixtures.
            if ($index < 2) {
                $draw = new TaskDraw();
                $draw->setOffer($offer);
                $draw->setTask($task);
                $draw->setPath($this->makeSketch($index));
                $draw->setBase64Data(null);

                $offer->addTaskDraw($draw);
                $task?->addTaskDraw($draw);

                $manager->persist($draw);
            }
        }

        $manager->flush();
    }

    /**
     * Draws a small carport elevation with GD — stand-in for something a fitter
     * would sketch on the tablet.
     *
     * @return string the filename written into the drawings directory
     */
    private function makeSketch(int $seed): string
    {
        $width = 1000;
        $height = 560;

        $image = imagecreatetruecolor($width, $height);

        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 15, 23, 42);
        $blue = imagecolorallocate($image, 34, 74, 190);
        $grey = imagecolorallocate($image, 148, 163, 184);

        imagefilledrectangle($image, 0, 0, $width, $height, $white);
        imagesetthickness($image, 5);

        $left = 170 + $seed * 30;
        $right = $width - 170;
        $roof = 170;
        $ground = 430;

        // roof slab
        imageline($image, $left - 40, $roof, $right + 40, $roof - 30, $ink);
        imageline($image, $left - 40, $roof + 26, $right + 40, $roof - 4, $ink);
        imageline($image, $left - 40, $roof, $left - 40, $roof + 26, $ink);
        imageline($image, $right + 40, $roof - 30, $right + 40, $roof - 4, $ink);

        // posts
        foreach ([$left, $right] as $x) {
            imageline($image, $x, $roof + 20, $x, $ground, $ink);
            imageline($image, $x + 22, $roof + 14, $x + 22, $ground, $ink);
            imageline($image, $x, $ground, $x + 22, $ground, $ink);
        }

        // ground line
        imagesetthickness($image, 3);
        imageline($image, 90, $ground, $width - 90, $ground, $grey);

        // solar panels on the roof
        imagesetthickness($image, 2);
        for ($i = 0; $i < 6; ++$i) {
            $x1 = $left - 20 + $i * 110;
            imageline($image, $x1, $roof + 8 - (int) ($i * 4.5), $x1 + 90, $roof + 3 - (int) ($i * 4.5), $blue);
        }

        // dimension line + label
        imagesetthickness($image, 2);
        imageline($image, $left, $ground + 45, $right, $ground + 45, $blue);
        imageline($image, $left, $ground + 35, $left, $ground + 55, $blue);
        imageline($image, $right, $ground + 35, $right, $ground + 55, $blue);
        imagestring($image, 5, (int) (($left + $right) / 2) - 30, $ground + 55, '6,00 m', $blue);
        imagestring($image, 5, 100, 90, 'Carport - Ansicht', $ink);

        $this->filesystem->mkdir($this->taskDrawingsDir);

        $filename = sprintf('demo-%d.png', $seed + 1);
        imagepng($image, $this->taskDrawingsDir.'/'.$filename, 6);

        return $filename;
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
