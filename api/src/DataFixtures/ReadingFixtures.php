<?php
namespace App\DataFixtures;

use App\Entity\Reading;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ReadingFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'vikram.melonfire@gmail.com']);
        if (!$user) {
            throw new \Exception('User not found in the database.');
        }

        // add dummy readings
        for ($i = 0; $i < 25; $i++) {
            $reading = new Reading();
            $reading->setValue(mt_rand(50, 250));
            $reading->setNote(mt_rand(0, 1) ? 'After meal' : null);
            $reading->setIsFasting((bool)random_int(0, 1));
            $reading->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', random_int(0, 7))));
            $reading->setUser($user);

            $manager->persist($reading);
        }

        $manager->flush();
    }
}
