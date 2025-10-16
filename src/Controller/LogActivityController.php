<?php

namespace App\Controller;

use App\Service\LogActivityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/log-activity', name: 'api_log_activity_')]
class LogActivityController extends AbstractController
{
    public function __construct(
        private LogActivityService $logActivityService
    ) {
    }

    /**
     * Récupère tous les logs d'activité
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function getAllLogActivities(): JsonResponse
    {
        try {
            $logActivities = $this->logActivityService->getAllLogActivities();
            
            return $this->json([
                'success' => true,
                'data' => $logActivities,
                'count' => count($logActivities)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les logs d'activité avec pagination
     */
    #[Route('/paginated', name: 'list_paginated', methods: ['GET'])]
    public function getLogActivitiesWithPagination(Request $request): JsonResponse
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 20);
            
            $result = $this->logActivityService->getLogActivitiesWithPagination($page, $limit);
            
            return $this->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'totalPages' => $result['totalPages']
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les logs d'activité par utilisateur
     */
    #[Route('/user/{userId}', name: 'by_user', methods: ['GET'])]
    public function getLogActivitiesByUser(int $userId): JsonResponse
    {
        try {
            $logActivities = $this->logActivityService->getLogActivitiesByUser($userId);
            
            return $this->json([
                'success' => true,
                'data' => $logActivities,
                'count' => count($logActivities)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les logs d'activité par catégorie
     */
    #[Route('/categorie/{categorie}', name: 'by_categorie', methods: ['GET'])]
    public function getLogActivitiesByCategorie(string $categorie): JsonResponse
    {
        try {
            $logActivities = $this->logActivityService->getLogActivitiesByCategorie($categorie);
            
            return $this->json([
                'success' => true,
                'data' => $logActivities,
                'count' => count($logActivities)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}