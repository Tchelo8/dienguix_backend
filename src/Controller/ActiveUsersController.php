<?php

namespace App\Controller;

use App\Service\ActiveUsersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ActiveUsersController extends AbstractController
{
    private ActiveUsersService $activeUsersService;

    public function __construct(ActiveUsersService $activeUsersService)
    {
        $this->activeUsersService = $activeUsersService;
    }

    /**
     * Récupère tous les utilisateurs actifs avec leurs statistiques
     */
    #[Route('/users/active', name: 'api_active_users', methods: ['GET'])]
    public function getActiveUsers(Request $request): JsonResponse
    {
        try {
            // Paramètres de requête
            $country = $request->query->get('country');
            $search = $request->query->get('search');
            $sortBy = $request->query->get('sort_by', 'total_amount_sent');
            $order = $request->query->get('order', 'desc');
            $limit = $request->query->getInt('limit', 0);
            $offset = $request->query->getInt('offset', 0);

            // Récupérer les utilisateurs
            $users = $this->activeUsersService->getActiveUsersWithStats();

            // Appliquer les filtres
            if ($country) {
                $users = $this->activeUsersService->getActiveUsersByCountry($country);
            }

            if ($search) {
                $users = $this->activeUsersService->searchActiveUsers($search);
            }

            // Trier les résultats
            $this->sortUsers($users, $sortBy, $order);

            // Pagination
            $total = count($users);
            if ($limit > 0) {
                $users = array_slice($users, $offset, $limit);
            }

            return new JsonResponse([
                'success' => true,
                'data' => array_values($users),
                'total' => $total,
                'count' => count($users),
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total
                ],
                'message' => 'Utilisateurs actifs récupérés avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la récupération des utilisateurs actifs',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les top envoyeurs
     */
    #[Route('/users/top-senders', name: 'api_top_senders', methods: ['GET'])]
    public function getTopSenders(Request $request): JsonResponse
    {
        try {
            $limit = $request->query->getInt('limit', 10);
            
            $topSenders = $this->activeUsersService->getTopSenders($limit);

            return new JsonResponse([
                'success' => true,
                'data' => $topSenders,
                'count' => count($topSenders),
                'message' => 'Top envoyeurs récupérés avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la récupération des top envoyeurs',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les top destinataires
     */
    #[Route('/users/top-receivers', name: 'api_top_receivers', methods: ['GET'])]
    public function getTopReceivers(Request $request): JsonResponse
    {
        try {
            $limit = $request->query->getInt('limit', 10);
            
            $topReceivers = $this->activeUsersService->getTopReceivers($limit);

            return new JsonResponse([
                'success' => true,
                'data' => $topReceivers,
                'count' => count($topReceivers),
                'message' => 'Top destinataires récupérés avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la récupération des top destinataires',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les détails d'un utilisateur actif spécifique
     */
    #[Route('/users/active/{id}', name: 'api_active_user_details', methods: ['GET'])]
    public function getActiveUserDetails(int $id): JsonResponse
    {
        try {
            $users = $this->activeUsersService->getActiveUsersWithStats();
            
            $user = array_filter($users, function($u) use ($id) {
                return $u['id'] == $id;
            });

            if (empty($user)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Utilisateur actif non trouvé',
                    'message' => "Aucun utilisateur actif trouvé avec l'ID $id"
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'data' => array_values($user)[0],
                'message' => 'Détails de l\'utilisateur récupérés avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la récupération des détails de l\'utilisateur',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les statistiques globales des utilisateurs actifs
     */
    #[Route('/users/active/stats/summary', name: 'api_active_users_summary', methods: ['GET'])]
    public function getActiveUsersSummary(): JsonResponse
    {
        try {
            $users = $this->activeUsersService->getActiveUsersWithStats();
            
            $totalUsers = count($users);
            $totalAmountSent = 0;
            $totalAmountReceived = 0;
            $totalTransactions = 0;
            $countriesStats = [];

            foreach ($users as $user) {
                $totalAmountSent += (float)str_replace(',', '', $user['total_amount_sent']);
                $totalAmountReceived += (float)str_replace(',', '', $user['total_amount_received']);
                $totalTransactions += $user['transaction_count'];

                // Stats par pays
                $country = $user['country'];
                if (!isset($countriesStats[$country])) {
                    $countriesStats[$country] = [
                        'country' => $country,
                        'currency' => $user['currency'],
                        'users_count' => 0,
                        'total_sent' => 0,
                        'total_received' => 0,
                        'total_transactions' => 0
                    ];
                }

                $countriesStats[$country]['users_count']++;
                $countriesStats[$country]['total_sent'] += (float)str_replace(',', '', $user['total_amount_sent']);
                $countriesStats[$country]['total_received'] += (float)str_replace(',', '', $user['total_amount_received']);
                $countriesStats[$country]['total_transactions'] += $user['transaction_count'];
            }

            // Formater les montants par pays
            foreach ($countriesStats as &$countryStats) {
                $countryStats['total_sent'] = number_format($countryStats['total_sent'], 2);
                $countryStats['total_received'] = number_format($countryStats['total_received'], 2);
            }

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'global_stats' => [
                        'total_active_users' => $totalUsers,
                        'total_amount_sent' => number_format($totalAmountSent, 2),
                        'total_amount_received' => number_format($totalAmountReceived, 2),
                        'total_transactions' => $totalTransactions,
                        'average_per_user' => $totalUsers > 0 ? number_format($totalTransactions / $totalUsers, 2) : '0.00'
                    ],
                    'countries_stats' => array_values($countriesStats)
                ],
                'message' => 'Résumé des utilisateurs actifs récupéré avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la récupération du résumé',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trie les utilisateurs selon les critères spécifiés
     */
    private function sortUsers(array &$users, string $sortBy, string $order = 'desc'): void
    {
        usort($users, function($a, $b) use ($sortBy, $order) {
            $valueA = $this->getSortValue($a, $sortBy);
            $valueB = $this->getSortValue($b, $sortBy);

            $comparison = $valueA <=> $valueB;
            
            return $order === 'desc' ? -$comparison : $comparison;
        });
    }

    /**
     * Récupère la valeur à utiliser pour le tri
     */
    private function getSortValue(array $user, string $sortBy): mixed
    {
        switch ($sortBy) {
            case 'total_amount_sent':
                return (float)str_replace(',', '', $user['total_amount_sent']);
            case 'total_amount_received':
                return (float)str_replace(',', '', $user['total_amount_received']);
            case 'transaction_count':
                return $user['transaction_count'];
            case 'average_amount':
                return (float)str_replace(',', '', $user['average_amount']);
            case 'last_login_at':
                return $user['last_login_at'] ? strtotime($user['last_login_at']) : 0;
            case 'created_at':
                return $user['created_at'] ? strtotime($user['created_at']) : 0;
            case 'name':
                return strtolower($user['name']);
            case 'email':
                return strtolower($user['email']);
            case 'country':
                return strtolower($user['country']);
            default:
                return $user['name'];
        }
    }
}