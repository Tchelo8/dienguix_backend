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


    
    #[Route('/update-rate/{id}', name: 'exchange_rate_update_rate', methods: ['PUT', 'PATCH'])]
    public function updateRate(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['rate'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le paramètre "taux" est requis'
                ], 400);
            }

            $newRate = $data['rate'];

            // Validation du taux
            if (!is_numeric($newRate) || $newRate <= 0) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le taux doit être un nombre positif'
                ], 400);
            }

            $result = $this->exchangeRateService->updateRate($id, $newRate);

            if (!$result) {
                return $this->json([
                    'success' => false,
                    'message' => 'Taux de change non trouvé'
                ], 404);
            }

            return $this->json([
                'success' => true,
                'message' => 'Taux mis à jour avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du taux',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}