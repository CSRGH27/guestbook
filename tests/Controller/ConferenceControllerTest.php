<?php

namespace App\Tests\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class ConferenceControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Give your feedback');
    }



    /**
     * Permet de simuler un test de commentaire
     *
     * @return void
     */
    public function testCommentSubmission()
    {
        $client = static::createClient();
        $client->request('GET', '/conference/amsterdam-2019');
        $client->submitForm('Submit', [
            'comment_form[author]' => 'Fabien',
            'comment_form[text]' => 'Some feedback from an automated functional test',
            'comment_form[email]' => $email = 'me@automat.fr',
            'comment_form[photoFilename]' => dirname(__DIR__, 2) . '/public/images/under-construction.gif',
        ]);

        $this->assertResponseRedirects();
        // TODO Simulation de la validation d'un commentaire  Self::Container->get permet d'acceder a n'importe quelle service qund on est dans un test unitaire php
        $comment = self::$container->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState('published');
        self::$container->get(EntityManagerInterface::class)->flush();
        $client->followRedirect();
        $this->assertSelectorExists('h4:contains("Fabien")');
    }

    /**
     * Permet de simuler une navigation sur une page et un click sur un lien
     *
     * @return void
     */
    public function testConferencePage()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('h4:contains("Fabien")');
    }


    //TODO  J'ai cree un alias dasn le bash pour effectuer les test rapidement sf-tests  

}
