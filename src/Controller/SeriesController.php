<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use App\Entity\User;
use App\Form\FollowType;
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
            ->findBy(['id' => 1]);

        return $this->render('series/index.html.twig', [
            'series' => $series,
        ]);
    }   

    /**
     * @Route("/{id}", name="series_show", methods={"GET", "POST"})
     */
    public function show(Series $series): Response
    {
        // To get the poster
        $stream = $series->getPoster();
        $poster = base64_encode(stream_get_contents($stream));

        // To get seasons
        $seasons = $this->getDoctrine()
            ->getRepository(Season::class)
            ->findBy(['series' => $series->getId()], ['number' => 'ASC']); // Get seasons about the serie instance

        // To get episodes
        for($i = 0; $i < count($seasons); $i++)
        {
            $episodes[$i] = $this->getDoctrine()
                ->getRepository(Episode::class)
                ->findBy(['season' => $seasons[$i]->getId()], ['number' => 'ASC']); // Get episodes about each season of the serie
        }

        // To print FollowForm
        $user = $this->getUser();
        $form = $this->createForm(FollowType::class, $user);

        if ($form->isSubmitted() && $form->isValid()) 
        {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('series_my'); // To show his new follow serie
        }

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'poster' => $poster,
            'seasons' => $seasons,
            'episodes' => $episodes,
            'followForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/myseries", name="series_my", methods={"GET"})
     */
    public function myseries(Series $series): Response
    {

        $user = $this->getUser();
        $user->addSeries($series);

        return $this->render('series/myseries.html.twig', [
            'series' => $series,
        ]);
    }


    /**
     * @Route("/new", name="series_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $series = new Series();
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($series);
            $entityManager->flush();

            return $this->redirectToRoute('series_index');
        }

        return $this->render('series/new.html.twig', [
            'series' => $series,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="series_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Series $series): Response
    {
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('series_index');
        }

        return $this->render('series/edit.html.twig', [
            'series' => $series,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="series_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Series $series): Response
    {
        if ($this->isCsrfTokenValid('delete'.$series->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('series_index');
    }

}