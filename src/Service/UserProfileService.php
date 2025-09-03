<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserProfileService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Récupère toutes les informations d'un utilisateur par son ID
     */
    public function getUserCompleteProfile(int $userId): array
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ];
        }

        return [
            'success' => true,
            'user' => $user->jsonSerialize()
        ];
    }

    /**
     * Récupère toutes les informations d'un utilisateur par son email
     */
    public function getUserCompleteProfileByEmail(string $email): array
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ];
        }
        return [
            'success' => true,
            'user' => $user->jsonSerialize()
        ];
    }

    /**
     * Récupère les informations simplifiées d'un utilisateur
     */
    public function getUserSimpleProfile(int $userId): array
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ];
        }

        $userData = $user->jsonSerializeSimple();
        
        // Ajouter les infos du profil en version simple si disponible
        if ($user->getUserProfile()) {
            $userData['profile'] = $user->getUserProfile()->jsonSerializeSimple();
        }

        return [
            'success' => true,
            'user' => $userData
        ];
    }

    /**
     * Récupère plusieurs utilisateurs avec leurs profils complets
     */
    public function getAllUsersWithProfiles(int $limit = 50, int $offset = 0): array
    {
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.userProfile', 'up')
            ->leftJoin('u.country', 'c')
            ->leftJoin('u.role', 'r')
            ->addSelect('up', 'c', 'r')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = $user->jsonSerialize();
        }

        return [
            'success' => true,
            'users' => $usersData,
            'count' => count($usersData)
        ];
    }
}