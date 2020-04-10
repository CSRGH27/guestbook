<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{

    /**
     * Undocumented variable
     *
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        for ($u = 0; $u < 25; $u++) {
            $conference = new Conference;
            $conference->setCity($faker->city);
            $conference->setYear($faker->year);
            $conference->setIsInternational(rand(0, 1));

            $manager->persist($conference);

            for ($i = 0; $i < 7; $i++) {
                $comment = new Comment;
                $comment->setAuthor($faker->name);
                $comment->setEmail($faker->email);
                $comment->setCreatedAt($faker->dateTime());
                $comment->setText($faker->realText($maxNbChars = 200, $indexSize = 2));
                $comment->setPhotoFilename($faker->imageUrl(640, 480, 'transport'));
                $comment->setConference($conference);

                $manager->persist($comment);
            }
        }
        $manager->flush();
    }
}
