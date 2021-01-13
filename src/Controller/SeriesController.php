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
use Gregwar\Cache\Cache;
use App\Repository\SeriesRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @Route("/series")
 */
class SeriesController extends AbstractController
{
    /**
     * @Route("/", name="series_index", methods={"GET", "POST"})
     */
    public function index(Request $request, SeriesRepository $repository, PaginatorInterface $paginator): Response
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

            if (count($genre) == 0){
                foreach ($genres as $g){
                    array_push($genre, $g->getName());
                }
                
            }
            $series = $repository->findCustom($title, $country, $genre, $sort);
            
        } else {
            $title = "";
            $country = "";
            $genre = array();
            foreach ($genres as $g){
                array_push($genre, $g->getName());
            }
            $sort = "";

            $series = $repository->findCustom($title, $country, $genre, $sort);
        }

        $series = $paginator->paginate(
            $series, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            15 // Nombre de résultats par page
        );

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'searchForm' => $searchForm->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="series_show", methods={"GET", "POST"}, requirements={"id":"\d+"})
     */
    public function show(Series $series, Request $request): Response
    {
        $user = $this->getUser(); // Connected user

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
    public function poster(Series $series): Response
    {
        
        $id = $series->getId();

        $cache = new Cache;
        $cache->setCacheDirectory('cache'); // This is the default
        $cache->setPrefixSize(0);

        // If the cache exists, this will return it, else, the closure will be called
        // to create this image
        $i = null;

        if (!$cache->exists("$id.txt", array())){
            $i = stream_get_contents($series->getPoster());
            $cache->set("$id.txt", $i);
        }
        $i = $cache->get("$id.txt", array());

        $response = new Response($i);

        $response->headers->set('Content-type', 'image/jpeg');


        /*$response = new Response($poster);

        $response->headers->set('Content-type', 'image/jpeg');*/

        return $response;
    }
}
