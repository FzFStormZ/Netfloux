<?php

namespace App\Controller;

use App\Form\UnFollowType;
use Symfony\Component\Form\FormView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    /**
     * @Route("/myseries", name="series_my", methods={"GET", "POST"})
     */
    public function myseries(Request $request): Response
    {
        // To get user' series
        $user = $this->getUser();
        $series = $user->getSeries(); // $series[] --> user can be more than 1 follow serie

        if (count($series) != 0)
        {
            $forms[] = new FormView();

            // To print UnFollowForm for each serie
            foreach($series as $serie)
            {
                $form = $this->get('form.factory')->createNamed('form_' . (string)$serie->getId(), UnFollowType::class, $user); // A FIX PROBLEM TO PUT IN "ABOUT PAGE". We have to give a name for each form to identified each form !!
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid() && $user) 
                {
                    $entityManager = $this->getDoctrine()->getManager();
                    $user->removeSeries($serie);
                    $entityManager->flush($user);

                    return $this->redirectToRoute('series_my'); // To show his new follow serie
                }

                $forms[$serie->getId()] = $form->createView();

                // To get the poster
                $stream = $serie->getPoster();
                $poster[$serie->getId()] = base64_encode(stream_get_contents($stream));
            }

            return $this->render('user/myseries.html.twig', [
                'series' => $series,
                'poster' => $poster,
                'unfollowForm' => $forms,
            ]);

        } else 
        {
            return $this->render('user/myseries.html.twig', [
                'series' => null,
                'poster' => null,
            ]);
        }
    }
}
