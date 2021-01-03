<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\Series;
use App\Entity\Episode;
use App\Form\WatchedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SeasonController extends AbstractController
{
    /**
     * @Route("/series/{series_id}/seasons/{seasons_id}", name="seasons_show", methods={"GET", "POST"}, requirements={"series_id":"\d+", "seasons_id":"\d+"})
     */
    public function index(Series $series_id, Season $seasons_id, Request $request): Response
    {
        // Variables
        $user = $this->getUser(); // Connected user (user = null is not)
        $episodes_not_watched = array(); // Array of Episodes not watched bu the user
        $forms = array();

        // To get episodes
        $episodes = $this->getDoctrine()
            ->getRepository(Episode::class)
            ->findBy(['season' => $seasons_id], ['number' => 'ASC']); // Get episodes about each season of the serie

        if ($user != null) {
            $episodes_not_watched = $episodes; // Add all episodes

            if (!($user->getEpisode()->isEmpty())) {
                
                // To delete watched an episode        
                foreach ($user->getEpisode() as $ep)
                {
                    foreach ($episodes as $episode)
                    {
                        if ($episode->getId() == $ep->getId()) {
                            $episodes_not_watched = $this->array_remove($episode, $episodes_not_watched); // Remove episode already watched
                            break;
                        }
                    }
                }
            }

            if (count($episodes_not_watched) != 0) // If the user hasn't seen already all episodes
            {
                foreach ($episodes_not_watched as $ep) {
                    $form = $this->get('form.factory')->createNamed('form_' . (string)$ep->getId(), WatchedType::class, $user);
                    $form->handleRequest($request);

                    if ($form->isSubmitted() && $form->isValid() && $user) {
                        $entityManager = $this->getDoctrine()->getManager();
                        $user->addEpisode($ep);
                        $entityManager->persist($user);
                        $entityManager->flush($user);

                        return $this->redirectToRoute('seasons_show', ['series_id' => $series_id->getId(), 'seasons_id' => $seasons_id->getId()]);
                    }

                    $forms[$ep->getId()] = $form->createView();
                }
            }
        }

        return $this->render('season/index.html.twig', [
            'series' => $series_id,
            'seasons' => $seasons_id,
            'episodes' => $episodes,
            'episodes_not_watched' => count($episodes_not_watched) != 0 ? $episodes_not_watched : $episodes_not_watched = null,
            'notWatchedForms' => $forms,
        ]);
    }

    // Used to episodes watched/not watched
    function array_remove($element, $array) {
        $index = array_search($element, $array);
        array_splice($array, $index, 1);
        return $array;
      }
}
