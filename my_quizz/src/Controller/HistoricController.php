<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;



final class HistoricController extends AbstractController
{
    #[Route('/historic', name: 'app_historic')]
    public function index(EntityManagerInterface $entityManager, Request  $request): Response
    {
        $entityManager->flush(); 

        $session = $request->getSession();
        $quizz_attempts = $session->get("quizz_attempts", []);


        return $this->render('historic/index.html.twig', [
            'controller_name' => 'HistoricController',
            'quizz_attempts' => $quizz_attempts

        ]);

        
    }
}
