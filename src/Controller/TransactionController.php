<?php

namespace App\Controller;

use App\Repository\CountryRepository;
use App\Service\TransactionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api/transactions', name: 'api_transactions_')]
class TransactionController extends AbstractController
{
    private TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Créer une nouvelle transaction
     */
    #[Route('/create/dgapp', name: 'create', methods: ['POST'])]
    public function makeTransaction(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                throw new BadRequestHttpException('Données JSON invalides');
            }
            $transaction = $this->transactionService->makeTransaction($data);
            return $this->json([
                'success' => true,
                'message' => 'Transaction créée avec succès',
                'data' => $transaction
            ], Response::HTTP_CREATED);
        } catch (BadRequestHttpException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la transaction'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer une transaction par ID
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function getTransaction(int $id): JsonResponse
    {
        try {
            $transaction = $this->transactionService->getTransactionById($id);

            return $this->json([
                'success' => true,
                'data' => $transaction
            ]);
        } catch (NotFoundHttpException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la transaction'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mettre à jour une transaction
     */
    #[Route('/update/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function updateTransaction(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                throw new BadRequestHttpException('Données JSON invalides');
            }

            $transaction = $this->transactionService->updateTransaction($id, $data);

            return $this->json([
                'success' => true,
                'message' => 'Transaction mise à jour avec succès',
                'data' => $transaction
            ]);
        } catch (NotFoundHttpException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (BadRequestHttpException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la transaction'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Soft delete d'une transaction
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function softDeleteTransaction(int $id): JsonResponse
    {
        try {
            $this->transactionService->softDeleteTransaction($id);

            return $this->json([
                'success' => true,
                'message' => 'Transaction supprimée avec succès'
            ]);
        } catch (NotFoundHttpException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la transaction'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer toutes les transactions d'un utilisateur
     */
    #[Route('/user/{userId}', name: 'user_transactions', methods: ['GET'])]
    public function getUserTransactions(int $userId, Request $request): JsonResponse
    {
        try {
            $filters = [
                'trans_status' => $request->query->get('status'),
                'transaction_type' => $request->query->get('type'),
                'date_from' => $request->query->get('date_from'),
                'date_to' => $request->query->get('date_to'),
                'limit' => $request->query->getInt('limit', 20),
                'offset' => $request->query->getInt('offset', 0)
            ];

            // Nettoyer les filtres vides
            $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            $transactions = $this->transactionService->getUserTransactions($userId, $filters);

            return $this->json([
                'success' => true,
                'data' => $transactions,
                'count' => count($transactions)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transactions utilisateur'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer les transactions envoyées par un utilisateur
     */
    #[Route('/user/{userId}/sent', name: 'user_sent_transactions', methods: ['GET'])]
    public function getSentTransactions(int $userId, Request $request): JsonResponse
    {
        try {
            $filters = $this->getFiltersFromRequest($request);
            $transactions = $this->transactionService->getSentTransactions($userId, $filters);

            return $this->json([
                'success' => true,
                'data' => $transactions,
                'count' => count($transactions)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transactions envoyées'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer les transactions reçues par un utilisateur
     */
    #[Route('/user/{userId}/received', name: 'user_received_transactions', methods: ['GET'])]
    public function getReceivedTransactions(int $userId, Request $request): JsonResponse
    {
        try {
            $filters = $this->getFiltersFromRequest($request);
            $transactions = $this->transactionService->getReceivedTransactions($userId, $filters);

            return $this->json([
                'success' => true,
                'data' => $transactions,
                'count' => count($transactions)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transactions reçues'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer toutes les transactions (admin)
     */
    #[Route('/list/all/dgapp', name: 'list', methods: ['GET'])]
    public function getAllTransactions(Request $request): JsonResponse
    {
        try {
            $filters = $this->getFiltersFromRequest($request);
            $filters['include_deleted'] = $request->query->getBoolean('include_deleted', false);

            $transactions = $this->transactionService->getAllTransactions($filters);

            return $this->json([
                'success' => true,
                'data' => $transactions,
                'count' => count($transactions)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transactions',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Statistiques des transactions d'un utilisateur
     */
    #[Route('/user/{userId}/stats', name: 'user_stats', methods: ['GET'])]
    public function getUserTransactionStats(int $userId): JsonResponse
    {
        try {
            $stats = $this->transactionService->getUserTransactionStats($userId);

            return $this->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Rechercher des transactions par référence
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function searchTransactions(Request $request): JsonResponse
    {
        try {
            $reference = $request->query->get('reference');

            if (!$reference) {
                throw new BadRequestHttpException('Le paramètre "reference" est requis');
            }

            $transactions = $this->transactionService->searchByReference($reference);

            return $this->json([
                'success' => true,
                'data' => $transactions,
                'count' => count($transactions)
            ]);
        } catch (BadRequestHttpException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche de transactions'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Méthode utilitaire pour extraire les filtres de la requête
     */
    private function getFiltersFromRequest(Request $request): array
    {
        $filters = [
            'trans_status' => $request->query->get('status'),
            'transaction_type' => $request->query->get('type'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
            'limit' => $request->query->getInt('limit', 20),
            'offset' => $request->query->getInt('offset', 0)
        ];

        // Nettoyer les filtres vides
        return array_filter($filters, fn($value) => $value !== null && $value !== '');
    }

    #[Route('/country/{id}/operators', methods: ['GET'])]
    public function getCountryOperators(int $id, CountryRepository $countryRepository): JsonResponse
    {
        $country = $countryRepository->find($id);

        if (!$country) {
            return $this->json(['error' => 'Country not found'], 404);
        }

        $operators = $country->getOperators()->map(fn($operator) => $operator->jsonSerialize())->toArray();

        return $this->json([
            'country' => $country->getName(),
            'operators' => $operators
        ]);
    }
}
