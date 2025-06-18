<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileEditForm;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Repository\QuizAttemptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;


use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(UserRepository $userRepository, EntityManagerInterface $entityManager, Request  $request, QuizAttemptRepository $quizAttemptRepository): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();


        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not authenticated or not an instance of App\Entity\User.');
        }
        $user->setLastActivity(lastActivity: new \DateTime());

        $entityManager->flush(); 

        $attempts = $quizAttemptRepository->findBy(
            ['user' => $user],
                ['attemptedAt' => 'DESC'] 
        );
        
        $attempts = array_slice($attempts, 0, 5); 


        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'user' => $user,
            'attempts' => $attempts

        ]);

    }
    
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not authenticated or not an instance of App\Entity\User.');
        }

        $originalEmail = $user->getEmail();


        $form = $this->createForm(ProfileEditForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
                if($plainPassword !== $confirmPassword){
                    $this->addFlash('error', 'Passwords do not match!');
                    return $this->redirectToRoute('app_profile_edit');
                }
            }

    
            $entityManager->flush();

            if ($originalEmail !== $user->getEmail()) {
                $this->emailVerifier->sendEmailConfirmation(
                    'app_verify_email',
                    $user,
                    (new TemplatedEmail())
                        ->from(new Address('yajjoumohamed@gmail.com', 'MyQuizz'))
                        ->to((string) $user->getEmail())
                        ->subject('Please Confirm your Email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );
            }

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
