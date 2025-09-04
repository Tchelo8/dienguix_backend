<?php

namespace App\Controller;

use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/dashboard', name: 'api_dashboard_')]
class DashboardController extends AbstractController
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * API: Statistiques globales
     * Retourne: total transactions, volume d'affaires Gabon, users actifs, taux d'erreur
     */
    #[Route('/stats', name: 'global_stats', methods: ['GET'])]
    public function getGlobalStats(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getGlobalStats();
            
            return $this->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistiques globales récupérées avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API: 5 dernières transactions
     * Retourne: expéditeur, destinataire, montant, statut, référence, date
     */
    #[Route('/last-transactions', name: 'last_transactions', methods: ['GET'])]
    public function getLastTransactions(): JsonResponse
    {
        try {
            $transactions = $this->dashboardService->getLastTransactions();
            
            return $this->json([
                'success' => true,
                'data' => $transactions,
                'count' => count($transactions),
                'message' => 'Dernières transactions récupérées avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transactions: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API: Performances journalières (dernières 24h depuis minuit) 
     * Retourne: transactions traitées, total transactions, nouveaux users, taux d'erreur
     */
    #[Route('/daily-performance', name: 'daily_performance', methods: ['GET'])]
    public function getDailyPerformance(): JsonResponse
    {
        try {
            $performance = $this->dashboardService->getDailyPerformance();
            
            return $this->json([
                'success' => true,
                'data' => $performance,
                'message' => 'Performances journalières récupérées avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des performances: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Route bonus: Toutes les données dashboard en une seule requête mais à utiliser sur mobile
     */
    #[Route('/summary', name: 'summary', methods: ['GET'])]
    public function getDashboardSummary(): JsonResponse
    {
        try {
            $globalStats = $this->dashboardService->getGlobalStats();
            $lastTransactions = $this->dashboardService->getLastTransactions();
            $dailyPerformance = $this->dashboardService->getDailyPerformance();
            
            return $this->json([
                'success' => true,
                'data' => [
                    'global_stats' => $globalStats,
                    'last_transactions' => $lastTransactions,
                    'daily_performance' => $dailyPerformance
                ],
                'message' => 'Résumé du dashboard récupéré avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du résumé: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}