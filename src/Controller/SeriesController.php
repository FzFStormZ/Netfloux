<?php

namespace App\Controller;

use DateTime;
use App\Entity\Genre;
use App\Entity\Rating;
use App\Entity\Season;
use App\Entity\Series;
use App\Entity\Country;
use App\Entity\Episode;
use App\Form\FollowType;
use App\Form\UnFollowType;
use App\Form\RatingType;
use App\Form\SearchType;
use App\Form\SeriesType;
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
    public function index(Request $request): Response
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
    

        /*
        $title = "";
        $country = null;
        $genre = null;
        $sort = "";
        */
        $tabRating = array();
        

        $searchForm = $this->createForm(SearchType::class, null, ['countries' => $countries, 'genres' => $genres]);
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            
            //Variables
            $title = $searchForm->get('title')->getData();
            $country = array($searchForm->get('country')->getData());
            $genre = array($searchForm->get('genre')->getData());
            $sort = $searchForm->get('sort')->getData();

            if ($country != "Choose a country" && $genre != "Choose a genre")
            {
                $series = $this->getDoctrine()
                    ->getRepository(Series::class)
                    ->findBy(["country" => $country, "genre" => $genre]);

            } else if ($country != "Choose a country" && $genre == "Choose a genre")
            {
                $series = $this->getDoctrine()
                    ->getRepository(Series::class)
                    ->findBy(["country" => $country]);

            } else if ($country == "Choose a country" && $genre != "Choose a genre")
            {
                $series = $this->getDoctrine()
                    ->getRepository(Series::class)
                    ->findBy(["genre" => $genre]);

            }

            if ($title != "")
            {
                $trueSeries = array();
                foreach ($series as $serie) {
                    if (str_contains(strtolower($serie->getTitle()), strtolower($title))) {
                        array_push($trueSeries, $serie);
                    }
                }

                $series = $trueSeries;
            }

            foreach ($series as $serie){
                $ratings = $this->getDoctrine()
                    ->getRepository(Rating::class)
                    ->findBy(['series' => $serie]);
    
                $avg = 0;
                
                if(count($ratings) > 0){
                    foreach ($ratings as $rating){
                        $avg += $rating->getValue();
                    }
                    $avg /= count($ratings);
                }
                $tabRating[$serie->getId()] = round($avg, 1);
                $seriesId[$serie->getId()] = $serie;
            }
    
            if ($sort == "Descending"){
                arsort($tabRating);
                $i = 0;
                $series = array();
                $keys = array_keys($tabRating);
                foreach ($tabRating as $rating) {
                    $series[$i] = $seriesId[$keys[$i]];
                    $i++;
                }
            } else if ($sort == "Ascending"){
                asort($tabRating);
                $i = 0;
                $series = array();
                $keys = array_keys($tabRating);
                foreach ($tabRating as $rating) {
                    $series[$i] = $seriesId[$keys[$i]];
                    $i++;
                }
            }

        }


        /*
        if (isset($_GET['title'])) {
            $title = $_GET['title'];
        } else {
            $title = "";
        }

        $series = array();
        $a = null;

        // Search by Country
        if (isset($_GET['country'])) {
            $country = $_GET['country'];
            if ($country != "") {
                $a = $this->getDoctrine()->getRepository(Country::class)->createQueryBuilder('c')
                    ->where('c.name LIKE :name')
                    ->setParameter('name', '%' . $country . '%')
                    ->getQuery()
                    ->getOneOrNullResult();

                    $series = $a->getSeries()->toArray();
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

                $genreSeries = $genreObj->getSeries()->toArray();

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

        if ($series2 != array()) {
            $series = $series2;
        }

        //Search by Title
        $trueSeries = array();

        if ($series == array()) {
            $series = $this->getDoctrine()->getRepository(Series::class)->createQueryBuilder('s')
                ->where('s.title LIKE :title')
                ->setParameter('title', '%' . $title . '%')
                ->getQuery()
                ->getResult();
        } else {
            if($title != ""){
                foreach ($series as $serie) {
                    
                        if (str_contains(strtolower($serie->getTitle()), strtolower($title))) {
                            array_push($trueSeries, $serie);
                        }
                    
                    
                }
                if ($trueSeries == array()) {
                    $series = array();
                }
            }
        }

        if ($trueSeries != array()) {
            $series = $trueSeries;
        }


        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        } else {
            $sort = "";
        }

        $seriesId = array();
        $tabRating = array();

        foreach ($series as $serie){
            $ratings = $this->getDoctrine()
                ->getRepository(Rating::class)
                ->findBy(['series' => $serie]);

            $avg = 0;
            
            if(count($ratings) > 0){
                foreach ($ratings as $rating){
                    $avg += $rating->getValue();
                }
                $avg /= count($ratings);
            }
            $tabRating[$serie->getId()] = round($avg, 1);
            $seriesId[$serie->getId()] = $serie;
        }

        if ($sort == "Descending"){
            arsort($tabRating);
            $i = 0;
            $series = array();
            $keys = array_keys($tabRating);
            foreach ($tabRating as $rating) {
                $series[$i] = $seriesId[$keys[$i]];
                $i++;
            }
        } else if ($sort == "Ascending"){
            asort($tabRating);
            $i = 0;
            $series = array();
            $keys = array_keys($tabRating);
            foreach ($tabRating as $rating) {
                $series[$i] = $seriesId[$keys[$i]];
                $i++;
            }
        }
        */

        
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
            array_push($poster, base64_encode(stream_get_contents($stream)));
        }
 

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'poster' => $poster,
            'countries' => $countries,
            'genres' => $genres,
            'currentPage' => $page,
            'maxPage' => $maxPage,
            'tabRating' => $tabRating,
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
        $seasons = $this->getDoctrine()
            ->getRepository(Season::class)
            ->findBy(['series' => $series->getId()], ['number' => 'ASC']); // Get seasons about the serie instance

        // To get episodes
        for ($i = 0; $i < count($seasons); $i++) {
            $episodes[$i] = $this->getDoctrine()
                ->getRepository(Episode::class)
                ->findBy(['season' => $seasons[$i]->getId()], ['number' => 'ASC']); // Get episodes about each season of the serie
        }

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
            'genres' => $genres,
            'countries' => $countries,
            'actors' => $actors,
            'episodes' => $episodes,
            'followForm' => $followForm->createView(),
            'unfollowForm' => $unfollowForm->createView(),
            'follow' => $follow,
            'ratingForm' => $ratingForm == null ? $ratingForm = null : $ratingForm->createView(), // If ratingForm is null, we return null. Else, we return createView() of the ratingForm
            'rating' => $rating,
            'found' => $found,
            'trailer' => $trailer,

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
