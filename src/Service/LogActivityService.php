<?php

namespace App\Service;

use App\Repository\LogActivityRepository;

class LogActivityService
{
    public function __construct(
        private LogActivityRepository $logActivityRepository
    ) {
    }

    /**
     * Récupère tous les logs d'activité
     * 
     * @return array
     */
    public function getAllLogActivities(): array
    {
        return $this->logActivityRepository->findAll();
    }

    /**
     * Récupère les logs d'activité avec pagination
     * 
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getLogActivitiesWithPagination(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        return [
            'data' => $this->logActivityRepository->findBy([], ['date' => 'DESC'], $limit, $offset),
            'total' => $this->logActivityRepository->count([]),
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($this->logActivityRepository->count([]) / $limit)
        ];
    }

    /**
     * Récupère les logs d'activité par utilisateur
     * 
     * @param int $userId
     * @return array
     */
    public function getLogActivitiesByUser(int $userId): array
    {
        return $this->logActivityRepository->findBy(['uzer' => $userId], ['date' => 'DESC']);
    }

    /**
     * Récupère les logs d'activité par catégorie
     * 
     * @param string $categorie
     * @return array
     */
    public function getLogActivitiesByCategorie(string $categorie): array
    {
        return $this->logActivityRepository->findBy(['categorie' => $categorie], ['date' => 'DESC']);
    }
}