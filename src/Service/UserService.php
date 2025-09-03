<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * Crée un utilisateur.
     * Si le rôle n'est pas admin, crée aussi un profil lié.
     */
    public function createUser(array $data, int $roleId): User
    {
        // D'abord faire une vérification pour savoir si l'utilisateur existe et vérifier l'unicité de l'email
        $existingUserByEmail = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUserByEmail) {
            throw new \InvalidArgumentException("Un compte avec cet email existe déjà.");
        }

        // Vérifier l'unicité du numéro de téléphone
        $existingUserByPhone = $this->em->getRepository(User::class)
            ->findOneBy(['phone' => $data['phone']]);

        if ($existingUserByPhone) {
            throw new \InvalidArgumentException("Un compte avec ce numéro de téléphone existe déjà.");
        }



        $user = new User();
        $user->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setEmail($data['email'])
            ->setPhone($data['phone'])
            ->setIsActive(true)
            ->setStatus(true)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTime())
            ->setLastLoginAt(new \DateTime());

        // Hash sécurisé du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Associer le rôle par ID
        $role = $this->em->getRepository(\App\Entity\Role::class)->find($roleId);
        if (!$role) {
            throw new \InvalidArgumentException("Role avec l'ID {$roleId} introuvable.");
        }

        $user->setRole($role);

        // Si ce n'est PAS un admin → on crée automatiquement un UserProfile
        if (strtoupper($role->getName()) !== 'Administrateur') {
            $profile = new UserProfile();
            $profile->setUzer($user)
                ->setAddress($data['address'] ?? '')
                ->setCity($data['city'] ?? '')
                ->setGender($data['gender'] ?? 'unknown')
                ->setBirthDate(new \DateTime($data['birth_date'] ?? '2000-01-01'))
                ->setVerified(false)
                ->setStatus(true)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTime())
                ->setDocumentNumber($data['document_number'] ?? '')
                ->setDocumentFile($data['document_file'] ?? '');
            $user->setUserProfile($profile);
            $this->em->persist($profile);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Récupère tous les utilisateurs avec leurs profils et rôles
     */
    public function getAllUsersWithProfiles(): array
    {
        $users = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.userProfile', 'p')
            ->leftJoin('u.role', 'r')
            ->leftJoin('u.country', 'c')
            ->addSelect('p', 'r', 'c')
            ->orderBy('u.created_at', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map([$this, 'formatUserData'], $users);
    }

    /**
     * Récupère un utilisateur par son ID avec son profil et son rôle
     */
    public function getUserWithProfile(int $id): ?array
    {
        $user = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.userProfile', 'p')
            ->leftJoin('u.role', 'r')
            ->leftJoin('u.country', 'c')
            ->addSelect('p', 'r', 'c')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $user ? $this->formatUserData($user) : null;
    }

    /**
     * Formate les données d'un utilisateur
     */
    private function formatUserData(User $user): array
    {
        $userData = [
            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFirstName() . ' ' . $user->getLastName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'is_active' => $user->isActive(),
            'status' => $user->isStatus(),
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'last_login_at' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
        ];

        // Ajouter les informations du pays
        if ($user->getCountry()) {
            $userData['country'] = [
                'id' => $user->getCountry()->getId(),
                'name' => $user->getCountry()->getName(),
                // Ajoutez d'autres propriétés du pays si nécessaires
            ];
        } else {
            $userData['country'] = null;
        }

        // Ajouter les informations du rôle
        if ($user->getRole()) {
            $userData['role'] = [
                'id' => $user->getRole()->getId(),
                'name' => $user->getRole()->getName(),
                // Ajoutez d'autres propriétés du rôle si nécessaires
            ];
        } else {
            $userData['role'] = null;
        }

        // Ajouter les informations du profil utilisateur
        if ($user->getUserProfile()) {
            $profile = $user->getUserProfile();
            $userData['profile'] = [
                'id' => $profile->getId(),
                'address' => $profile->getAddress(),
                'city' => $profile->getCity(),
                'gender' => $profile->getGender(),
                'birth_date' => $profile->getBirthDate()?->format('Y-m-d'),
                'verified' => $profile->isVerified(),
                'status' => $profile->isStatus(),
                'document_number' => $profile->getDocumentNumber(),
                'document_file' => $profile->getDocumentFile(),
                'created_at' => $profile->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $profile->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        } else {
            $userData['profile'] = null;
        }

        // Statistiques des transactions
        $userData['transactions_stats'] = [
            'sent_count' => $user->getTransactionSender()->count(),
            'received_count' => $user->getTransactionReceiver()->count(),
            'total_transactions' => $user->getTransactionSender()->count() + $user->getTransactionReceiver()->count()
        ];

        return $userData;
    }

    /**
     * Récupère les utilisateurs actifs uniquement
     */
    public function getActiveUsersWithProfiles(): array
    {
        $users = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.userProfile', 'p')
            ->leftJoin('u.role', 'r')
            ->leftJoin('u.country', 'c')
            ->addSelect('p', 'r', 'c')
            ->where('u.is_active = :active')
            ->setParameter('active', true)
            ->orderBy('u.created_at', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map([$this, 'formatUserData'], $users);
    }

    /**
     * Récupère les utilisateurs par rôle
     */
    public function getUsersByRole(int $roleId): array
    {
        $users = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.userProfile', 'p')
            ->leftJoin('u.role', 'r')
            ->leftJoin('u.country', 'c')
            ->addSelect('p', 'r', 'c')
            ->where('u.role = :roleId')
            ->setParameter('roleId', $roleId)
            ->orderBy('u.created_at', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map([$this, 'formatUserData'], $users);
    }

    /**
     * Récupère les utilisateurs avec pagination
     */
    public function getUsersWithPagination(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        $users = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.userProfile', 'p')
            ->leftJoin('u.role', 'r')
            ->leftJoin('u.country', 'c')
            ->addSelect('p', 'r', 'c')
            ->orderBy('u.created_at', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Compter le total
        $totalUsers = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'users' => array_map([$this, 'formatUserData'], $users),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => (int) $totalUsers,
                'total_pages' => ceil($totalUsers / $limit),
                'has_next_page' => $page < ceil($totalUsers / $limit),
                'has_previous_page' => $page > 1
            ]
        ];
    }

    /**
     * Modifier le rôle d'un utilisateur 
     */
    public function updateUserRole(int $userId, string $newRoleName): array
    {
        try {
            // Récupérer l'utilisateur
            $user = $this->em->getRepository(User::class)->find($userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                    'code' => 404
                ];
            }

            // Récupérer le nouveau rôle
            $role = $this->em->getRepository(\App\Entity\Role::class)->findOneBy(['name' => $newRoleName]);

            if (!$role) {
                return [
                    'success' => false,
                    'message' => 'Rôle introuvable',
                    'code' => 404
                ];
            }

            // Vérifier si le rôle n'est pas déjà assigné
            if ($user->getRole()->getName() === $newRoleName) {
                return [
                    'success' => false,
                    'message' => 'L\'utilisateur a déjà ce rôle',
                    'code' => 400
                ];
            }

            // Mettre à jour le rôle
            $user->setRole($role);
            $user->setUpdatedAt(new \DateTime());

            $this->em->flush();

            return [
                'success' => true,
                'message' => 'Rôle mis à jour avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'full_name' => $user->getFirstName() . ' ' . $user->getLastName(),
                    'email' => $user->getEmail(),
                    'role' => [
                        'id' => $role->getId(),
                        'name' => $role->getName()
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la modification du rôle: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    public function updateUserStatus(int $userId, bool $newStatus): array
    {
        try {
            // Récupérer l'utilisateur
            $user = $this->em->getRepository(User::class)->find($userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                    'code' => 404
                ];
            }

            // Mettre à jour le statut
            $user->setIsActive($newStatus);
            $user->setStatus($newStatus); 
            $user->setUpdatedAt(new \DateTime());

            $this->em->flush();

            return [
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'full_name' => $user->getFirstName() . ' ' . $user->getLastName(),
                    'email' => $user->getEmail(),
                    'is_active' => $user->isActive(),
                    'status' => $user->isStatus()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la modification du statut: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }
}
