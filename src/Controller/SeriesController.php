<?php

namespace App\Controller;

use App\Entity\Series;
use App\Form\SeriesType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/series")
 */
class SeriesController extends AbstractController
{
    /**
     * @Route("/", name="series_index", methods={"GET"})
     */
    public function index(): Response
    {
        $series = $this->getDoctrine()
            ->getRepository(Series::class)
            ->findAll();

        return $this->render('series/index.html.twig', [
            'series' => $series,
        ]);
    }   

    /**
     * @Route("/{id}", name="series_show", methods={"GET"})
     */
    public function show(Series $series): Response
    {
        $stream = $series->getPoster();
        $poster = base64_encode(stream_get_contents($stream));
        
        return $this->render('series/show.html.twig', [
            'series' => $series,
            'poster' => $poster
        ]);
    }
}
