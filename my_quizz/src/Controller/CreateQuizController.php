<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CategorieRepository;
use App\Repository\QuestionRepository;
use App\Entity\Categorie;
use App\Entity\Question;
use App\Entity\Reponse;




final class CreateQuizController extends AbstractController
{
    #[Route('/create/quiz', name: 'quiz_create')]
public function createQuiz(Request $request, EntityManagerInterface $entityManager): Response
{
    if ($request->isMethod('POST')) {
        $name = $request->request->get('quiz_name');
        $planned = (int)$request->request->get('planned_questions');

        $quiz = new Categorie(); 
        $quiz->setName($name);
        $quiz->setNumberQuestion($planned);
        $entityManager->persist($quiz);
        $entityManager->flush();

        return $this->redirectToRoute('quiz_add_question', [
            'id' => $quiz->getId()
        ]);
    }

    return $this->render('create_quiz/index.html.twig');
}


#[Route('/create/quiz/{id}/add-question', name: 'quiz_add_question')]
public function createQuestions(int $id, CategorieRepository $categorieRepository, Request $request, EntityManagerInterface $entityManager): Response
{
    $categorie = $categorieRepository->find($id);

    if (!$categorie) {
        throw $this->createNotFoundException("Quiz introuvable.");
    }

    $totalQuestionsPlanned = $categorie->getNumberQuestion();

    $questionsAlreadyAdded = $categorie->getQuestions()->count();
    $currentQuestionNumber = $questionsAlreadyAdded + 1;
    $moreToGo = $totalQuestionsPlanned - $currentQuestionNumber + 1;


    if ($currentQuestionNumber > $totalQuestionsPlanned && $totalQuestionsPlanned > 0) {
        $this->addFlash('success', 'All questions for the quiz "' . $categorie->getName() . '" have been added!');
        return $this->redirectToRoute('admin_quizz'); 
    }

    if ($request->isMethod('POST')) {
        $question = $request->request->get('question_name');
        $responsesArray = $request->request->all('responses'); 

        $questions = new Question();
        $questions->setCategorie($categorie);
        $questions->setQuestion($question);
        $entityManager->persist($questions);
    
        foreach ($responsesArray as $responseData) {
            $reponse = new Reponse();
            // dd($reponse);
            $reponse->setQuestion($questions); 
            $reponse->setReponse($responseData['label'] ?? '');
            $reponse->setReponseExpected(!empty($responseData['is_correct']));
            $entityManager->persist($reponse);
        }
    
        $entityManager->flush();
 
        return $this->redirectToRoute('quiz_add_question', ['id' => $id]);
    }
    

    return $this->render('create_quiz/add_question.html.twig', [
        'quiz' => $categorie,
        'totalQuestionsPlanned' => $totalQuestionsPlanned,
        'currentQuestionNumber' => $currentQuestionNumber,
        'moreToGo' => $moreToGo
    ]);
}

}
