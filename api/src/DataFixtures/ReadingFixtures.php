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
        $email = getenv('USER_EMAIL') ?: 'vikram.melonfire@gmail.com';
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'vikram.melonfire@gmail.com']);
        if (!$user) {
            throw new \Exception('User not found in the database.');
        }

        // add dummy readings
        $types = ['fasting', 'post-prandial', 'random'];
        for ($i = 0; $i < 40; $i++) {
            $reading = new Reading();
            $reading->setValue(mt_rand(50, 250));
            $randomType = $types[array_rand($types)];
            $reading->setType($randomType);
            $reading->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', random_int(0, 15))));
            $reading->setUser($user);

            $manager->persist($reading);
        }

        $manager->flush();
    }
}
