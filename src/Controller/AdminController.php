<?php

namespace App\Controller;

use App\Entity\Actor;
use App\Entity\Country;
use App\Entity\Genre;
use App\Entity\Rating;
use App\Entity\Series;
use App\Form\ImdbType;
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
        $i = 0;
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
                    $commentForm = $this->get('form.factory')->createNamed('form_' . (string)$i, CommentType::class, $rating);
                    $commentForm->handleRequest($request);

                    if ($commentForm->isSubmitted() && $commentForm->isValid() && $admin) {

                        $entityManager = $this->getDoctrine()->getManager();
                        $rating->setComment("");
                        $entityManager->flush($rating);

                        return $this->redirectToRoute('admin_comments');
                    }

                    $commentsForm[$i] = $commentForm->createView();
                }

                $i++;    
            }
        }
            
        return $this->render('admin/comments.html.twig', [
            'ratings' => $ratings,
            'commentsForm' => $commentsForm,
        ]);
    }

    /**
     * @Route("/series_add", name="admin_series_add")
     */  
    public function series_add(Request $request): Response
    {
        $admin = $this->getUser();

        if(isset($_POST['imdb_id']))
        {
            $url = "http://www.omdbapi.com/?apikey=48e8fab3&i=" . $_POST['imdb_id'];
            $data = file_get_contents($url);
            $serie = json_decode($data, true);

            $newSerie = new Series();
            $form = $this->createForm(ImdbType::class, $newSerie);
            $form->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid() && $admin) {
    
                $entityManager = $this->getDoctrine()->getManager();
                $newSerie->setTitle($serie["Title"]);
                $newSerie->setPlot($serie["Plot"]);
                $newSerie->setImdb($_POST['imdb_id']);
                $newSerie->setPoster(file_get_contents($serie["Poster"]));
                $newSerie->setDirector($serie["Director"]);
                $newSerie->setAwards($serie["Awards"]);

                $years = explode($serie["Year"], "-");
                if (count($years) == 1)
                {
                    $newSerie->setYearStart($years[0]);
                } else
                {
                    $newSerie->setYearStart($years[0]);
                    $newSerie->setYearEnd($years[1]);
                }

                $actors = explode($serie["Actors"], ", ");
                foreach ($actors as $name)
                {
                    $actor = new Actor();
                    $actor->setName($name);
                    $newSerie->addActor($actor);
                }

                $countries = explode($serie["Country"], ", ");
                foreach ($countries as $name)
                {
                    $country = new Country();
                    $country->setName($name);
                    $newSerie->addCountry($country);
                }

                $genres = explode($serie["Genre"], ", ");
                foreach ($genres as $name)
                {
                    $genre = new Genre();
                    $genre->setName($name);
                    $newSerie->addGenre($genre);
                }
    
                $entityManager->persist($newSerie);
                $entityManager->flush();
                // do anything else you need here, like send an email
    
                return $this->redirectToRoute('series_index');
            }
        }

       

        return $this->render('admin/series_add.html.twig', [
            'form' => isset($_POST['imdb_id']) ? $form->createView() : $form = null,

        ]);
    }
}
