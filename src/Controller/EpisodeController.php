<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\Series;
use App\Entity\Episode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EpisodeController extends AbstractController
{
    /**
     * @Route("/series/{serie_id}/seasons/{season_id}/episode/{ep_id}", name="episode_show", methods={"GET", "POST"}, requirements={"series_id":"\d+", "seasons_id":"\d+"})
     */
    public function index(Series $serie_id, Season $season_id, Episode $ep_id): Response
    {
        return $this->render('episode/index.html.twig', [
            'serie' => $serie_id,
            'season' => $season_id,
            'episode' => $ep_id,
        ]);
    }
}
