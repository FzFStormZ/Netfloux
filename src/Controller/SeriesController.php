<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Country;
use App\Entity\Genre;
use App\Entity\Season;
use App\Entity\Series;
use App\Entity\User;
use App\Entity\Rating;
use App\Form\FollowType;
use App\Form\SeriesType;
use App\Form\RatingType;
use DateTime;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Date;

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


        if (isset($_GET['title'])) {
            $title = $_GET['title'];
        } else {
            $title = "";
        }

        $series = null;

        // Search by Country
        if (isset($_GET['country'])) {
            $country = $_GET['country'];
            if ($country != "") {
                $countryObj = $this->getDoctrine()->getRepository(Country::class)->createQueryBuilder('c')
                    ->where('c.name LIKE :name')
                    ->setParameter('name', '%' . $country . '%')
                    ->getQuery()
                    ->getOneOrNullResult();

                $countrySeries = $countryObj->getSeries();

                $series = $countrySeries;
            }
        } else {
            $country = "";
        }

        $series2 = array();
        // Search by Genre
        if (isset($_GET['genre'])) {
            $genre = $_GET['genre'];
            if ($genre != "") {
                $genreObj = $this->getDoctrine()->getRepository(Genre::class)->createQueryBuilder('g')
                    ->where('g.name LIKE :name')
                    ->setParameter('name', '%' . $genre . '%')
                    ->getQuery()
                    ->getOneOrNullResult();

                $genreSeries = $genreObj->getSeries();
                if ($series == array()) {
                    $series2 = $genreSeries;
                } else {
                    foreach ($series as $serie) {
                        foreach ($genreSeries as $genreSerie) {
                            if ($serie->getTitle() == $genreSerie->getTitle()) {
                                array_push($series2, $serie);
                            }
                        }
                    }
                }
            }
        } else {
            $genre = "";
        }

        if ($series2 != null) {
            $series = $series2;
        }
        //Search by Title
        $trueSeries = array();
        if ($series == null) {
            $series = $this->getDoctrine()->getRepository(Series::class)->createQueryBuilder('s')
                ->where('s.title LIKE :title')
                ->setParameter('title', '%' . $title . '%')
                ->getQuery()
                ->getResult();
        } else {
            foreach ($series as $serie) {
                if (str_contains(strtolower($serie->getTitle()), strtolower($title))) {
                    array_push($trueSeries, $serie);
                }
            }
            if ($trueSeries == array()) {
                $series = array();
            }
        }

        if ($trueSeries != null) {
            $series = $trueSeries;
        }

        if (isset($_GET['page'])) {
            $page = (int)$_GET['page'];
        } else {
            $page = 0;
        }


        $lenght = 18;

        if ($page < 0) {
            $page = 0;
        } else if ($page * $lenght > count($series)) {
            $page--;
        }

        if (count($series) > $page * 15 + $lenght) {
            $tmp = array_slice($series, ($page * $lenght), $lenght);
        } else {
            $tmplenght = $page * 15 + $lenght - count($series);
            $tmp = array_slice($series, ($page * $lenght), $tmplenght);
        }

        $series = $tmp;






        $poster = null;
        for ($i = 0; $i < count($series); $i++) {
            $stream = $series[$i]->getPoster();
            $poster[$i] = base64_encode(stream_get_contents($stream));
        }

        // To get Countries
        $countries = $this->getDoctrine()
            ->getRepository(Country::class)
            ->findAll();

        // To get Genres
        $genres = $this->getDoctrine()
            ->getRepository(Genre::class)
            ->findAll();

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'poster' => $poster,
            'countries' => $countries,
            'genres' => $genres,
            'currentTitle' => $title,
            'currentCountry' => $country,
            'currentGenre' => $genre,
            'currentPage' => $page,
        ]);
    }

    /**
     * @Route("/{id}", name="series_show", methods={"GET", "POST"}, requirements={"id":"\d+"})
     */
    public function show(Series $series, Request $request): Response
    {
        $user = $this->getUser(); // Connected user


        // To get the poster
        $stream = $series->getPoster();
        $poster = base64_encode(stream_get_contents($stream));

        // To get seasons
        $seasons = $this->getDoctrine()
            ->getRepository(Season::class)
            ->findBy(['series' => $series->getId()], ['number' => 'ASC']); // Get seasons about the serie instance

        // To get episodes
        for ($i = 0; $i < count($seasons); $i++) {
            $episodes[$i] = $this->getDoctrine()
                ->getRepository(Episode::class)
                ->findBy(['season' => $seasons[$i]->getId()], ['number' => 'ASC']); // Get episodes about each season of the serie
        }


        // To print FollowForm
        $followForm = $this->createForm(FollowType::class, $user);
        $followForm->handleRequest($request);

        if ($followForm->isSubmitted() && $followForm->isValid() && $user) {

            $entityManager = $this->getDoctrine()->getManager();
            $user->addSeries($series);
            $entityManager->flush($user);

            return $this->redirectToRoute('series_my'); // To show his new follow serie
        }


        // Variables for rating
        $ratings = $this->getDoctrine()
            ->getRepository(Rating::class)
            ->findAll(); // Get seasons about the serie instance
        $ratingForm = null;
        $found = false;

        // Find if we have a rating for this serie and for this user
        foreach ($ratings as $ratingT) {
            if ($ratingT->getSeries() == $series && $ratingT->getUser() == $user) {
                $rating = $ratingT;
                $found = true;
            }
        }

        // If we don't found a rating for this serie and for this user, we print a RatingForm
        if ($found == false) {
            $rating = new Rating();

            //To print RatingForm if not rated
            $ratingForm = $this->createForm(RatingType::class, $rating);
            $ratingForm->handleRequest($request);

            if ($ratingForm->isSubmitted() && $ratingForm->isValid() && $user) {
                $entityManager = $this->getDoctrine()->getManager();
                $rating->setSeries($series);
                $rating->setUser($user);
                $rating->setValue($ratingForm->get('rating')->getData());

                date_default_timezone_set('Europe/Paris'); // Not forget this !!
                $rating->setDate(new DateTime());

                $comment = $ratingForm->get('comment');

                if ($comment != null) // Comment is optional 
                {
                    $rating->setComment($comment->getData());
                }

                $entityManager->persist($rating);
                $entityManager->flush($rating);

                $ratingForm = $ratingForm->createView();

                return $this->redirectToRoute('series_show', ['id' => $series->getId()]);
            }
        }

        // Variables for follow/unfollow
        $follow = false;

        // The follow/unfollow button can be visible only for connected user
        if ($user != null) {
            // To know is this serie is follow or not by the user
            $series_user = $user->getSeries();
            $follow = false;

            foreach ($series_user as $serie) {
                if ($serie->getId() == $series->getId()) {
                    $follow = true;
                }
            }
        }

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'poster' => $poster,
            'seasons' => $seasons,
            'episodes' => $episodes,
            'followForm' => $followForm->createView(),
            'follow' => $follow,
            'ratingForm' => $ratingForm == null ? $ratingForm = null : $ratingForm->createView(), // If ratingForm is null, we return null. Else, we return createView() of the ratingForm
            'rating' => $rating,
            'found' => $found,

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
        if ($this->isCsrfTokenValid('delete' . $series->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('series_index');
    }
}
