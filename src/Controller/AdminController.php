<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Message\CommentMessage;
use Symfony\Component\Workflow\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
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
     * @Route("/admin/comment/review/{id}", name="review_comment")
     *
     * @param Request $request
     * @param Comment $comment
     * @param Registry $registry
     * @return void
     */
    public function reviewComment(Request $request, Comment $comment, Registry $registry)
    {
        $accepted = !$request->query->get('reject');

        $machine = $registry->get($comment);

        if ($machine->can($comment, 'publish')) {
            $transition = $accepted ? 'publish' : 'reject';
        } elseif ($machine->can($comment, 'publish_ham')) {
            $transition = $accepted ? 'publish_ham' : 'reject_ham';
        } else {
            return new Response('Comment already reviewed or not in the right place');
        }

        $machine->apply($comment, $transition);
        $this->em->flush();

        if ($accepted) {
            $this->bus->dispatch(new CommentMessage($comment->getId()));
        }

        return $this->render('admin/review.html.twig', [
            'transition' => $transition,
            'comment' => $comment
        ]);
    }
}
