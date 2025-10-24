<?php

namespace App\Controller;

use App\Service\ExchangeRateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/exchange-rate')]
class ExchangeRateController extends AbstractController
{
    public function __construct(
        private ExchangeRateService $exchangeRateService
    ) {}

    #[Route('/dashboard-stats', name: 'exchange_rate_dashboard_stats', methods: ['GET'])]
    public function getDashboardStats(): JsonResponse
    {
        try {
            $stats = $this->exchangeRateService->getDashboardStats();
            
            return $this->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/stats/period', name: 'exchange_rate_stats_period', methods: ['GET'])]
    public function getStatsByPeriod(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query->get('start_date');
            $endDate = $request->query->get('end_date');

            if (!$startDate || !$endDate) {
                return $this->json([
                    'success' => false,
                    'message' => 'Les paramètres start_date et end_date sont requis (format: Y-m-d)'
                ], 400);
            }

            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate . ' 23:59:59');

            $stats = $this->exchangeRateService->getStatsByPeriod($start, $end);
            
            return $this->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}