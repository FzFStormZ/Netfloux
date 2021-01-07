<?php

namespace App\Controller;

use DateTime;
use App\Entity\Genre;
use App\Entity\Country;
use App\Entity\Rating;
use App\Entity\Series;
use App\Form\FollowType;
use App\Form\UnFollowType;
use App\Form\RatingType;
use App\Form\SearchType;
use App\Form\CommentType;
use App\Repository\SeriesRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/series")
 */
class SeriesController extends AbstractController
{
    /**
     * @Route("/", name="series_index", methods={"GET", "POST"})
     */
    public function index(Request $request, SeriesRepository $repository): Response
    {
        // Variables
        $countries = $this->getDoctrine()
            ->getRepository(Country::class)
            ->findAll();
        $genres = $this->getDoctrine()
            ->getRepository(Genre::class)
            ->findAll();
        $series = $this->getDoctrine()
            ->getRepository(Series::class)
            ->findAll();
        $sort = 'DESC';
        

        $searchForm = $this->createForm(SearchType::class, null, ['countries' => $countries, 'genres' => $genres]);
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            
            //Variables
            $title = $searchForm->get('title')->getData();
            $country = $searchForm->get('country')->getData();
            $genre = $searchForm->get('genre')->getData();
            $sort = $searchForm->get('sort')->getData();

            $series = $repository->findCustom($title, $country, $genre);

            
        }

        $avg = $repository->findAllAndAverage($sort);
        
        // A CORRIGER
        if (isset($_GET['page'])) {
            $page = (int)$_GET['page'];
        } else {
            $page = 1;
        }

        $lenght = 18;

        if ($page < 1){
            $page = 1;
        } else if(($page-1)*$lenght > count($series)) {
            $page--;
        }

        if(count($series) > ($page-1)*$lenght+$lenght){
            $tmp = array_slice($series, (($page-1)*$lenght), $lenght);
        } else {
            $tmplenght = count($series) - ($page-1)*$lenght;
            $tmp = array_slice($series, (($page-1)*$lenght), $tmplenght);
        }

        $maxPage = (int)(count($series) / $lenght)+1;

        $series = $tmp;

        $poster = array();

        foreach($series as $serie){
            $stream = $serie->getPoster();
            //array_push($poster[$serie->getId()], base64_encode(stream_get_contents($stream)));
        }
 

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'poster' => $poster,
            'countries' => $countries,
            'genres' => $genres,
            'currentPage' => $page,
            'maxPage' => $maxPage,
            'tabRating' => $avg,
            'searchForm' => $searchForm->createView(),
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
        $seasons = $series->getSeasons();

        // To get genres
        $genres = $series->getGenre();

        // To get countries
        $countries = $series->getCountry();

        // To get actors
        $actors = $series->getActor();

        // To get YouTube trailer
        $trailer = $series->getYoutubeTrailer();
        $trailer = str_replace("watch?v=", "embed/", $trailer);

        // To print FollowForm
        $followForm = $this->createForm(FollowType::class, $user);
        $followForm->handleRequest($request);

        if ($followForm->isSubmitted() && $followForm->isValid() && $user) {

            $entityManager = $this->getDoctrine()->getManager();
            $user->addSeries($series);
            $entityManager->flush($user);

            return $this->redirectToRoute('series_my'); // To show his new follow serie
        }

        // To print unFollowForm
        $unfollowForm = $this->createForm(UnFollowType::class, $user);
        $unfollowForm->handleRequest($request);

        if ($unfollowForm->isSubmitted() && $unfollowForm->isValid() && $user) {

            $entityManager = $this->getDoctrine()->getManager();
            $user->removeSeries($series);
            $entityManager->flush($user);

            return $this->redirectToRoute('series_show', ['id' => $series->getId()]);
        }

        // Get ratings about the serie
        $ratings = $series->getRatings();
        $ratingForm = null;
        $found = false;

        // Find if we have a rating for this user
        foreach ($ratings as $ratingT) {
            if ($ratingT->getUser() == $user) {
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
        $commentForm = $this->get('form.factory')->createNamed('form_' . (string)$rating->getId(), CommentType::class, $rating);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid() && $user) {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($rating);
            $entityManager->flush($rating);

            return $this->redirectToRoute('series_show', ['id' => $series->getId()]);
        }

        $commentForm = $commentForm->createView();
        

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'poster' => $poster,
            'seasons' => $seasons,
            'genres' => $genres,
            'countries' => $countries,
            'actors' => $actors,
            'followForm' => $followForm->createView(),
            'unfollowForm' => $unfollowForm->createView(),
            'follow' => $follow,
            'ratingForm' => $ratingForm == null ? $ratingForm = null : $ratingForm->createView(), // If ratingForm is null, we return null. Else, we return createView() of the ratingForm
            'rating' => $rating,
            'found' => $found,
            'trailer' => $trailer,
            'commentForm' => $commentForm,

        ]);
    }

    /**
     * @Route("/poster/{id}", name="series_poster", methods={"GET", "POST"}, requirements={"id":"\d+"})
     */
    public function poster(Series $series, Request $request): Response
    {

        $stream = $series->getPoster();
        $poster = stream_get_contents($stream);

        $response = new Response($this->render('series/poster.html.twig', [
            'poster' => $poster,
        ]));

        $response->headers->set('Content-type', 'image/jpeg');

        return $response;
    }
}
