<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
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
            for($i = 0; $i < count($series); $i++)
            {
                $form = $this->get('form.factory')->createNamed('form_' . (string)$i, UnFollowType::class, $user); // A FIX PROBLEM TO PUT IN "ABOUT PAGE". We have to give a name for each form to identified each form !!
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid() && $user) 
                {
                    $entityManager = $this->getDoctrine()->getManager();
                    $user->removeSeries($series[$i]);
                    $entityManager->flush($user);

                    return $this->redirectToRoute('series_my'); // To show his new follow serie
                }

                $forms[$i] = $form->createView();

                // To get the poster
                $stream = $series[$i]->getPoster();
                $poster[$i] = base64_encode(stream_get_contents($stream));
            }

            return $this->render('user/myseries.html.twig', [
                'series' => $series,
                'poster' => $poster,
                'unFollowForm' => $forms,
            ]);

        } else 
        {
            return $this->render('user/myseries.html.twig', [
                'series' => null,
                'poster' => null,
            ]);
        }
    }
    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index');
    }
}
