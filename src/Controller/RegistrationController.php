<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Entity\Country;
use App\Form\RegistrationFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = new User();
        $countries = $this->getDoctrine()
            ->getRepository(Country::class)
            ->findAll();

        $form = $this->createForm(RegistrationFormType::class, $user, ['countries' => $countries]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            // Get data
            $country = $form->get('country')->getData();
            $otherCountry = $form->get('otherCountry')->getData();

            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            
            // Set Register Date automatically
            date_default_timezone_set('Europe/Paris');
            $user->setRegisterDate(new DateTime());

            // If the otherCountry is empty
            if ($otherCountry == "")
            {
                if ($country == 'Choose a country')
                {
                    $user->setCountry(null);
                } else 
                {
                    $user->setCountry($country);
                }
                
            } else
            {
                $country = new Country();
                $country->setName($otherCountry);
                $user->setCountry($country);

                $entityManager->persist($country);
            }
            
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('series_index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
