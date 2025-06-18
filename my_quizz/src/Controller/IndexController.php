<?php

namespace App\Controller;


use App\Repository\CategorieRepository;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use App\Entity\User;
use App\Entity\QuizAttempt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\VarDumper\VarDumper;

 class IndexController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(CategorieRepository $categorieRepository): Response
    {
        $categories = $categorieRepository->findAll();
        return $this->render('index.html.twig', [
            'controller_name' => 'IndexController',
            'categories' => $categories,
        ]);
    }

#[Route('/quiz/{category}/{numero}', name: 'quiz_question')]
public function show(
    CategorieRepository $categorieRepository,
    QuestionRepository $questionRepository,
    ReponseRepository $reponseRepository,
    Request $request,
    EntityManagerInterface $entityManager,
    int $category,
    int $numero
): Response {
    $session = $request->getSession();

        $categoryEntity = $categorieRepository->find($category);

        if (!$categoryEntity) {
            throw $this->createNotFoundException('Catégorie non trouvée.');
        }
        // dd($categoryEntity);


        $questions = $questionRepository->findBy(['categorie' => $categoryEntity]);
        $totalQuestionsPlanned = $categoryEntity->getNumberQuestion();


            if (count($questions) < $totalQuestionsPlanned){

                $this->addFlash('type', 'Please complete the quiz first !');
                return $this->redirectToRoute('index');

            }

    if (!isset($questions[$numero - 1])) {
        // derniere question
        $answers = $session->get('quiz_answers', []);
        $score = 0;

        foreach ($answers as $reponseId) {
            $reponse = $reponseRepository->find($reponseId);
            if ($reponse && $reponse->isReponseExpected()) {
                $score++;
            }
            // var_dump($score);
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            $currentUser->setTotalQuizzes($currentUser->getTotalQuizzes() + 1);
            $currentUser->setTotalPoints($currentUser->getTotalPoints() + $score);
            $currentUser->setTotalTimeSpent($currentUser->getTotalTimeSpent() + 5);

            $attempt = new QuizAttempt();
            $attempt->setUser($currentUser);
            $attempt->setCategorie($categoryEntity);
            $attempt->setScore($score);
            $attempt->setAttemptedAt(new \DateTime());
        
            $entityManager->persist($attempt);
            $entityManager->persist($currentUser);
            $entityManager->flush();
        }


        $quizz_attempts = $session->get('quizz_attempts', []);
        $quizz_attempts[] = [
            'categoryName' => $categoryEntity->getName(),
            'score' => $score,
            'totalQuestions' => count($questions),
        ];
 
        if (count($quizz_attempts) > 5) {
            $quizz_attempts = array_slice($quizz_attempts, -5);
        }
        $session->set('quizz_attempts', $quizz_attempts);


        $session->remove('quiz_answers');
        return $this->render('quizz/end.html.twig', [
            'score' => $score,
            'total' => count($questions),
        ]);
        
    }

    $question = $questions[$numero - 1];

    if ($request->isMethod('POST')) {
        $selectedAnswer = $request->request->get('answer');
        $answers = $session->get('quiz_answers', []);
        $answers[$question->getId()] = $selectedAnswer;
        $session->set('quiz_answers', $answers);

        return $this->redirectToRoute('quiz_question', [
            'category' => $category,
            'numero' => $numero + 1,
        ]);
    }

    return $this->render('quizz/question.html.twig', [
        'question' => $question,
        'numero' => $numero,
        'category' => $categoryEntity,
        'total' => count($questions),
    ]);
}

}