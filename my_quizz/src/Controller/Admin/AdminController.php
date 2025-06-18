<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Categorie;
use App\Form\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\QuizAttemptRepository;
use App\Repository\UserRepository;
use App\Repository\CategorieRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', ['hide_navbar' => true]);
    }

    #[Route('/users', name: 'users')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            // dd($users),
            'hide_navbar' => true
        ]);
    }

    #[Route('/stats_users', name: 'stats_user')]
    public function statsUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/stats_user.html.twig', [
            'users' => $users,
            'hide_navbar' => true
        ]);
    }


    #[Route('/stats_quizz', name: 'stats_quiz')]
    public function statsQuizz(
        Request $request,
        CategorieRepository $categorieRepository,
        QuizAttemptRepository $quizAttemptRepository,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $categories = $categorieRepository->findAll();
        $categoryStats = [];

        $allAttempts = $quizAttemptRepository->findAll();

        $categoryData = [];

        foreach ($allAttempts as $attempt) {
            $category = $attempt->getCategorie();
            if (!$category) continue;

            $catId = $category->getId();

            if (!isset($categoryData[$catId])) {
            $categoryData[$catId] = [
                'category' => $category,
                'totalAttempts' => 0,
                'totalScore' => 0,
                ];
            }

            $categoryData[$catId]['totalAttempts']++;
            $categoryData[$catId]['totalScore'] += $attempt->getScore();
        }

        foreach ($categories as $category) {
            $catId = $category->getId();
            $totalQuestions = count($category->getQuestions());
            $attempts = $categoryData[$catId]['totalAttempts'] ?? 0;
            $totalScore = $categoryData[$catId]['totalScore'] ?? 0;
            $maxPossibleScore = $totalQuestions * $attempts;
            $successRate = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;

            $categoryStats[$catId] = [
                'name' => $category->getName(),
                'numberOfQuestions' => $totalQuestions,
                'totalAttempts' => $attempts,
                'successRate' => round($successRate, 2),
            ];
        }

        $period = $request->query->get('period', 'month');

        $dayData = array_fill(0, 24, 0);
        $weekData = array_fill(0, 7, 0);
        $monthData = array_fill(1, 12, 0);
        $yearData = [2023=> 0, 2022=> 0, 2021=> 0, 2024=>0];

        foreach ($allAttempts as $attempt) {
            $date = $attempt->getAttemptedAt();
            if (!$date) continue;

            $dayData[(int)$date->format('G')]++;
            $weekData[(int)$date->format('N') - 1]++;
            $monthData[(int)$date->format('n')]++;
            $year = $date->format('Y');
            $yearData[$year] = ($yearData[$year] ?? 0) + 1;
        }

        $chartTimeLabels = [];
        $chartTimeData = [];
        $chartDatasetLabel = '';

        switch ($period) {
            case 'day':
                $chartTimeLabels = array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23));
                $chartTimeData = $dayData;
                $chartDatasetLabel = 'Total Attempts by Hour (All Time)';
                break;

            case 'week':
                $chartTimeLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                $chartTimeData = $weekData;
                $chartDatasetLabel = 'Total Attempts by Day of Week (All Time)';
                break;

            case 'month':
                $chartTimeLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $chartTimeData = array_values($monthData);
                $chartDatasetLabel = 'Total Attempts by Month (All Years)';
                break;

            case 'year':
                if (!empty($yearData)) {
                    ksort($yearData);
                    $chartTimeLabels = array_keys($yearData);
                    $chartTimeData = array_values($yearData);
                } else {
                    $chartTimeLabels = ['No Data'];
                    $chartTimeData = [0];
                }
                $chartDatasetLabel = 'Total Attempts by Year';
                break;

            default:
                $chartTimeLabels = ['Select a period'];
                $chartTimeData = [0];
                $chartDatasetLabel = 'No Period Selected';
            }

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $chartTimeLabels,
            'datasets' => [
                [
                    'label' => $chartDatasetLabel,
                    'backgroundColor' => 'oklch(54.6% 0.245 262.881)',
                    'borderColor' => 'oklch(54.6% 0.245 262.881)',
                    'data' => $chartTimeData,
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ]);

        return $this->render('admin/stats_quizz.html.twig', [
            'categories' => $categories,
            'hide_navbar' => true,
            'categoryStats' => $categoryStats,
            'chart' => $chart,
            'currentPeriod' => $period,
        ]);
    }
    #[Route('/quizz', name: 'quizz')]
    public function listQuizz(CategorieRepository $categorieRepository): Response
    {
        $categories = $categorieRepository->findAll();
        return $this->render('admin/quizz.html.twig', [
            'controller_name' => 'IndexController',
            'categories' => $categories,
            'hide_navbar' => true
        ]);
    }

    #[Route('/users/{id}/toggle', name: 'toggle', methods: ['POST'])]
    public function toggleAdminRole(User $user, EntityManagerInterface $em, Request $request): Response
    {
        $isAdmin = $request->request->get('is_admin');

        if ($isAdmin !== null) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $user->setRoles(['ROLE_USER']);
            } else {
                $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
            }

            $em->persist($user);
            $em->flush();
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/verify', name: 'verified_toggle', methods: ['POST'])]
    public function toggleIsVerified(User $user, EntityManagerInterface $em, Request $request): Response
    {
            if ($user->isVerified() == false)
            {
                $user->setIsVerified(true);
            } 
            else 
            {
                $user->setIsVerified(false);
            }

            $em->persist($user);
            $em->flush();
        // }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/delete', name: 'delete', methods: ['POST'])]
    public function deleteProfil(EntityManagerInterface $em, User $user): Response
    {
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/quizz/{id}/delete', name: 'delete_quizz', methods: ['POST'])]
    public function deleteQuizz(EntityManagerInterface $em, Categorie $categorie): Response
    {
        foreach ($categorie->getQuestions() as $question) {
            $em->remove($question);
        }

        $em->remove($categorie);
        $em->flush();

        return $this->redirectToRoute('admin_quizz');
    }

    #[Route('/users/create', name: 'create_user')]
    public function createUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(AdminUser::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Déterminer les rôles
            $isAdmin = $form->get('isAdmin')->getData();
            $user->setRoles($isAdmin ? ['ROLE_USER', 'ROLE_ADMIN'] : ['ROLE_USER']);

            $user->setIsVerified(true); // Tu peux ajuster selon besoin

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/create_user.html.twig', [
            'form' => $form,
            'hide_navbar' => true,
        ]);
    }

}