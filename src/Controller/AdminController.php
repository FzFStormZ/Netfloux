<?php

namespace App\Controller;

use DateTime;
use App\Entity\Actor;
use App\Entity\Genre;
use App\Entity\Rating;
use App\Entity\Season;
use App\Entity\Series;
use App\Form\ImdbType;
use App\Entity\Country;
use App\Entity\Episode;
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
                    $commentForm = $this->get('form.factory')->createNamed('form_' . (string)$rating->getId(), CommentType::class, $rating);
                    $commentForm->handleRequest($request);

                    if ($commentForm->isSubmitted() && $commentForm->isValid() && $admin) {

                        $entityManager = $this->getDoctrine()->getManager();
                        $rating->setComment("");
                        $entityManager->flush($rating);

                        return $this->redirectToRoute('admin_comments');
                    }

                    $commentsForm[$rating->getId()] = $commentForm->createView();
                }
            }
        }
            
        return $this->render('admin/comments.html.twig', [
            'ratings' => $ratings,
            'commentsForm' => $commentsForm,
        ]);
    }

    /**
     * @Route("/series_add", name="admin_series_add", methods={"GET", "POST"})
     */  
    public function series_add(Request $request): Response
    {
        $admin = $this->getUser();
        $exist = false;
        $newSerie = new Series();
        $imdbForm = $this->createForm(ImdbType::class, $newSerie);
        $imdbForm->handleRequest($request);

        if ($imdbForm->isSubmitted() && $imdbForm->isValid() && $admin) {

            // To get imdbId that the admin give us
            $id = $imdbForm->get("imdb")->getData();

            $exist = $this->getDoctrine()
            ->getRepository(Series::class)
            ->findOneBy(['imdb'=>$id]);

            if ($exist == null){
                // Get all data about the serie
                $url = "http://www.omdbapi.com/?apikey=48e8fab3&i=" . $id;
                $data = file_get_contents($url);

                // If the ID is correct
                if ($data != false)
                {
                    $serie = json_decode($data, true);

                    $entityManager = $this->getDoctrine()->getManager();
                    $newSerie->setTitle($serie["Title"]);
                    $newSerie->setPlot($serie["Plot"]);
                    $newSerie->setImdb($id);
                    $newSerie->setPoster(file_get_contents($serie["Poster"]));
                    $newSerie->setDirector($serie["Director"]);
                    $newSerie->setAwards($serie["Awards"]);

                    // To handle YouTubeTrailer
                    $apikey = 'AIzaSyDLLoG7jzja108UYpLC0CXe2d7hqrOluNY'; 
                    $googleApiUrl = 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=trailer+' . str_replace(" ", "+", $serie["Title"]) . '&maxResults=1&key=' . $apikey;

                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_URL, $googleApiUrl);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($ch);

                    curl_close($ch);
                    $data = json_decode($response);
                    $value = json_decode(json_encode($data), true);

                    $videoId = $value['items'][0]['id']['videoId'];

                    $newSerie->setYoutubeTrailer("https://www.youtube.com/watch?v=" . $videoId);

                    // To handle YearStar and YearEnd
                    $years = explode("–", $serie["Year"]); // BIG DIFFICULTY "–" VS "-"
                    $newSerie->setYearStart((int)$years[0]);
                    $newSerie->setYearEnd(count($years) > 1 ? ((int)$years[1] != 0 ? (int)$years[1] : null) : null);

                    // To handle a multiple actors
                    $actors = explode(", ", $serie["Actors"]);
                    foreach ($actors as $name)
                    {
                        // Check if the actor already exists in the database
                        $actor = $this->getDoctrine()
                            ->getRepository(Actor::class)
                            ->findOneBy(['name' => $name]);

                        if ($actor == null)
                        {
                            $actor = new Actor();
                            $actor->setName($name);
                            $newSerie->addActor($actor);
                        } else 
                        {
                            $newSerie->addActor($actor);
                        }
                    
                        $entityManager->persist($actor);
                    }

                    // To handle multiple countries
                    $countries = explode(", ", $serie["Country"]);
                    foreach ($countries as $name)
                    {
                        // Check if the country already exists in the database
                        $country = $this->getDoctrine()
                            ->getRepository(Country::class)
                            ->findOneBy(['name' => $name]);

                        if ($country == null)
                        {
                            $country = new Country();
                            $country->setName($name);
                            $newSerie->addCountry($country);
                        } else 
                        {
                            $newSerie->addCountry($country);
                        }
                        
                        $entityManager->persist($country);
                    }

                    // To handle multiple genres
                    $genres = explode(", ", $serie["Genre"]);
                    foreach ($genres as $name)
                    {
                        // Check if the genre already exists in the database
                        $genre = $this->getDoctrine()
                            ->getRepository(Genre::class)
                            ->findOneBy(['name' => $name]);
                        
                        if ($genre == null)
                        {
                            $genre = new Genre();
                            $genre->setName($name);
                            $newSerie->addGenre($genre);
                        } else {
                            $newSerie->addGenre($genre);
                        }

                        $entityManager->persist($genre);
                    }

                    // To handle seasons and for each season, all episodes of it
                    $nbSeasons = $serie['totalSeasons'];
                    for ($i = 1; $i <= $nbSeasons; $i++)
                    {
                        $season = new Season();
                        $season->setNumber($i);
                        $season->setSeries($newSerie);

                        $dataEpisodes = file_get_contents($url . "&Season=" . $i);
                        if ($dataEpisodes != false)
                        {
                            $episodes = json_decode($dataEpisodes, true);
                            $onlyEpisodes = $episodes["Episodes"];
                            if (count($onlyEpisodes) != 0)
                            {
                                foreach ($onlyEpisodes as $ep)
                                {
                                    $episode = new Episode();
                                    $episode->setTitle($ep["Title"]);
                                    $episode->setDate($ep["Released"] != "N/A" ? new DateTime($ep["Released"]) : null); // String to DateTime format
                                    $episode->setImdb($ep["imdbID"]);
                                    $episode->setImdbrating($ep["imdbRating"] != "N/A" ? $ep["imdbRating"] : null);
                                    $episode->setNumber($ep["Episode"]);
                                    $episode->setSeason($season);

                                    $entityManager->persist($episode);
                                }
                            } else 
                            {
                                $episode = new Episode();
                                $entityManager->persist($episode);
                            }
                                
                        }

                        $entityManager->persist($season);
                    }

                    $entityManager->persist($newSerie);
                    $entityManager->flush();

                    return $this->redirectToRoute('series_show', ["id" => $newSerie->getId()]);
                }
                $exist = false;
            } else {
                $exist = true;
            }


            
                
        }

        return $this->render('admin/new.html.twig', [
            'imdbForm' => $imdbForm->createView(),
            'admin' => $admin,
            'exist' => $exist,
        ]);
    }
}
