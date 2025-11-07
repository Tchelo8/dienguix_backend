<?php

namespace App\Controller;

use App\Service\OperatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/operators', name: 'api_operators_')]
class OperatorController extends AbstractController
{
    private OperatorService $operatorService;

    public function __construct(OperatorService $operatorService)
    {
        $this->operatorService = $operatorService;
    }

    /**
     * API principale : Récupère tous les pays avec leurs opérateurs et statistiques de l'opérateur by #tchelooooo
     */
    #[Route('/from/country/dgapp', name: 'countries_with_operators', methods: ['GET'])]
    public function getCountriesWithOperators(): JsonResponse
    {
        try {
            $data = $this->operatorService->getCountriesWithOperatorsStats();

            return new JsonResponse([
                'success' => true,
                'message' => 'Pays et opérateurs récupérés avec succès',
                'data' => $data,
                'count' => count($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère tous les opérateurs actifs avec leurs statistiques
     */
    #[Route('', name: 'all_operators', methods: ['GET'])]
    public function getAllOperators(): JsonResponse
    {
        try {
            $data = $this->operatorService->getAllOperators();

            return new JsonResponse([
                'success' => true,
                'message' => 'Opérateurs récupérés avec succès',
                'data' => $data,
                'count' => count($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des opérateurs',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user-country', name: 'operators_by_user_country', methods: ['GET'])]
    public function getOperatorsByUserCountry(Request $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur connecté
            /** @var User $user */
            $user = $this->getUser();
  
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Vérifier que l'utilisateur a un pays
            if (!$user->getCountry()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'L\'utilisateur n\'a pas de pays associé'
                ], Response::HTTP_BAD_REQUEST);
            }

            $countryId = $user->getCountry()->getId();
            $data = $this->operatorService->getOperatorsByUserCountry($countryId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Opérateurs récupérés avec succès',
                'data' => $data,
                'count' => count($data),
                'country' => [
                    'id' => $user->getCountry()->getId(),
                    'name' => $user->getCountry()->getName()
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des opérateurs',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les opérateurs d'un pays (format simplifié sans statistiques)
     */
    #[Route('/country/{countryId}/simple', name: 'operators_by_country_simple', methods: ['GET'])]
    public function getOperatorsByCountrySimple(int $countryId): JsonResponse
    {
        try {
            $data = $this->operatorService->getOperatorsByCountrySimple($countryId);

            if (empty($data)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Pays non trouvé ou aucun opérateur actif'
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Opérateurs récupérés avec succès',
                'data' => $data,
                'count' => count($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des opérateurs',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Récupère les statistiques d'un opérateur spécifique
     */
    #[Route('/{id}/stats', name: 'operator_stats', methods: ['GET'])]
    public function getOperatorStats(int $id): JsonResponse
    {
        try {
            $stats = $this->operatorService->getOperatorStats($id);

            if (!$stats) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Opérateur non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Statistiques de l\'opérateur récupérées avec succès',
                'data' => $stats
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les opérateurs d'un pays avec leurs statistiques
     */
    #[Route('/country/{countryId}', name: 'operators_by_country', methods: ['GET'])]
    public function getOperatorsByCountry(int $countryId): JsonResponse
    {
        try {
            $data = $this->operatorService->getOperatorsByCountry($countryId);

            if (empty($data)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Pays non trouvé ou aucun opérateur actif'
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Opérateurs du pays récupérés avec succès',
                'data' => $data,
                'operators_count' => count($data['operators'])
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des opérateurs',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère le top des opérateurs par montant total
     */
    #[Route('/top', name: 'top_operators', methods: ['GET'])]
    public function getTopOperators(Request $request): JsonResponse
    {
        try {
            $limit = $request->query->getInt('limit', 10);

            // Limiter à un maximum de 100 pour éviter les surcharges
            $limit = min($limit, 100);

            $data = $this->operatorService->getTopOperatorsByAmount($limit);

            return new JsonResponse([
                'success' => true,
                'message' => 'Top des opérateurs récupéré avec succès',
                'data' => $data,
                'count' => count($data),
                'limit' => $limit
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération du top des opérateurs',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère un résumé global des statistiques
     */
    #[Route('/summary', name: 'operators_summary', methods: ['GET'])]
    public function getOperatorsSummary(): JsonResponse
    {
        try {
            $countries = $this->operatorService->getCountriesWithOperatorsStats();

            $summary = [
                'total_countries' => count($countries),
                'total_operators' => 0,
                'total_amount_sent' => 0,
                'total_commission' => 0,
                'total_transactions_sent' => 0,
                'total_transactions_received' => 0
            ];

            foreach ($countries as $country) {
                $summary['total_operators'] += count($country['operators']);

                foreach ($country['operators'] as $operator) {
                    $stats = $operator['statistics'];
                    $summary['total_amount_sent'] += (float) $stats['montant_total'];
                    $summary['total_commission'] += (float) $stats['commission'];
                    $summary['total_transactions_sent'] += $stats['nombre_envois'];
                    $summary['total_transactions_received'] += $stats['nombre_receptions'];
                }
            }

            // Formatage des montants
            $summary['total_amount_sent'] = number_format($summary['total_amount_sent'], 2, '.', '');
            $summary['total_commission'] = number_format($summary['total_commission'], 2, '.', '');

            return new JsonResponse([
                'success' => true,
                'message' => 'Résumé des statistiques récupéré avec succès',
                'data' => $summary
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la génération du résumé',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
