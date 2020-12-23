<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SeasonController extends AbstractController
{
    /**
     * @Route("/series/{series_id}/seasons/{seasons_id}", name="seasons_show", methods={"GET"}, requirements={"series_id":"\d+", "seasons_id":"\d+"})
     */
    public function index(Series $series_id, Season $seasons_id): Response
    {
        $user = $this->getUser(); // Connected user

        // To get episodes
        $episodes = $this->getDoctrine()
            ->getRepository(Episode::class)
            ->findBy(['season' => $seasons_id], ['number' => 'ASC']); // Get episodes about each season of the serie


        return $this->render('season/index.html.twig', [
            'series' => $series_id,
            'seasons' => $seasons_id,
            'episodes' => $episodes,
        ]);
    }
}
