<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EpisodeController extends AbstractController
{
    /**
     * @Route("/episode", name="episode")
     */
    public function index(): Response
    {
        return $this->render('episode/index.html.twig', [
            'controller_name' => 'EpisodeController',
        ]);
    }
}
