<?php

namespace App\Controller;

use App\Service\UserProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user', name: 'api_user_')]
class UserProfileController extends AbstractController
{
    public function __construct(
        private UserProfileService $userProfileService
    ) {}

    /**
     * Récupère le profil complet de l'utilisateur connecté
     */
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCurrentUserProfile(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $result = $this->userProfileService->getUserCompleteProfile($user->getId());
        
        return $this->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Récupère le profil complet d'un utilisateur par son ID
     */
    #[Route('/profile/{id}', name: 'profile_by_id', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')] // Seuls les admins peuvent voir les autres profils
    public function getUserProfileById(int $id): JsonResponse
    {
        $result = $this->userProfileService->getUserCompleteProfile($id);
        
        return $this->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Récupère le profil simple de l'utilisateur connecté
     */
    #[Route('/profile/simple', name: 'profile_simple', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCurrentUserSimpleProfile(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $result = $this->userProfileService->getUserSimpleProfile($user->getId());
        
        return $this->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Récupère la liste de tous les utilisateurs avec leurs profils (admin seulement)
     */
    #[Route('/profiles', name: 'all_profiles', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllUserProfiles(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 50);
        $offset = $request->query->getInt('offset', 0);
        
        // Validation des limites
        $limit = min(max($limit, 1), 100); // Entre 1 et 100
        $offset = max($offset, 0); // Minimum 0

        $result = $this->userProfileService->getAllUsersWithProfiles($limit, $offset);
        
        return $this->json($result);
    }

    /**
     * Recherche un utilisateur par email (admin seulement)
     */
    #[Route('/search', name: 'search_by_email', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function searchUserByEmail(Request $request): JsonResponse
    {
        $email = $request->query->get('email');
        
        if (!$email) {
            return $this->json([
                'success' => false,
                'message' => 'Email requis'
            ], 400);
        }

        $result = $this->userProfileService->getUserCompleteProfileByEmail($email);
        
        return $this->json($result, $result['success'] ? 200 : 404);
    }
}