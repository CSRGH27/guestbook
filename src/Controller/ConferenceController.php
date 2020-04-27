<?php

namespace App\Controller;

use App\Entity\Comment;
use Twig\Environment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManager;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;

class ConferenceController extends AbstractController
{
    private $twig;
    private $em;
    private $bus;

    public function __construct(Environment $twig, EntityManagerInterface $em, MessageBusInterface $bus)
    {
        $this->twig = $twig;
        $this->em = $em;
        $this->bus = $bus;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function index(ConferenceRepository $conferenceRepository)
    {
        return new Response($this->twig->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]));
    }

    /**
     * @Route("/conference/{slug}", name="conference")
     */
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository, string $photoDir)
    {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);
            if ($photo = $form['photoFilename']->getData()) {
                $filename = bin2hex(random_bytes(6)) . '.' . $photo->guessExtension();
                try {
                    $photo->move($photoDir, $filename);
                } catch (FileException $e) {
                    // unable to uplaod photo, give up
                }
                $comment->setPhotoFilename($filename);
            }


            $this->em->persist($comment);
            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];

            $this->em->flush();
            $this->bus->dispatch((new CommentMessage($comment->getId(), $context)));

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }



        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);


        return new Response($this->twig->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form->createView(),

        ]));
    }
}
