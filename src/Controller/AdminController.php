<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Form\CommentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="admin_panel", methods={"GET"})
     */  
    public function panel(): Response
    {
        $admin = $this->getUser();

        return $this->render('admin/panel.html.twig', [
            'admin' => $admin,
        ]);
    }

    /**
     * @Route("/comments", name="admin_comments", methods={"GET", "POST"})
     */  
    public function comments(Request $request): Response
    {
        $admin = $this->getUser();
        $i = 0;
        $commentsForm = array();
        
        $ratings = $this->getDoctrine()
            ->getRepository(Rating::class)
            ->findAll();

        if ($ratings != null)
        {
            foreach ($ratings as $rating)
            {
                if ($rating->getComment() != "")
                {
                    $commentForm = $this->get('form.factory')->createNamed('form_' . (string)$i, CommentType::class, $rating);
                    $commentForm->handleRequest($request);

                    if ($commentForm->isSubmitted() && $commentForm->isValid() && $admin) {

                        $entityManager = $this->getDoctrine()->getManager();
                        $rating->setComment("");
                        $entityManager->flush($rating);

                        return $this->redirectToRoute('admin_comments');
                    }

                    $commentsForm[$i] = $commentForm->createView();
                }

                $i++;    
            }
        }
            
        return $this->render('admin/comments.html.twig', [
            'ratings' => $ratings,
            'commentsForm' => $commentsForm,
        ]);
    }

    /**
     * @Route("/series_add", name="admin_series_add")
     */  
    public function series_add(): Response
    {


        return $this->render('admin/series_add.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
