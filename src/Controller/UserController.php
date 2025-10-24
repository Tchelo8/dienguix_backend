<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\User;
use App\Service\EmailService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/list/all/dgapp', name: 'api_users_list', methods: ['GET'])]
    public function getAllUsers(): JsonResponse
    {
        try {
            $users = $this->userService->getAllUsersWithProfiles();

            return $this->json([
                'success' => true,
                'data' => $users,
                'count' => count($users)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/users/{id}', name: 'api_user_detail', methods: ['GET'])]
    public function getUserById(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserWithProfile($id);

            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            return $this->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    #[Route('/invite', name: 'invite_user', methods: ['POST'])]
    public function inviteUser(Request $request, EmailService $emailService, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $email = $data['email'] ?? null;
            $role = $data['role'] ?? 'client';
            $message = $data['message'] ?? '';

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse(['success' => false, 'message' => 'Email invalide'], 400);
            }

            // Vérifier si l'utilisateur existe déjà
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                return new JsonResponse(['success' => false, 'message' => 'Un utilisateur avec cet email existe déjà'], 400);
            }

            // Générer un token unique
            $inviteToken = bin2hex(random_bytes(32));

            // Sauvegarder l'invitation en base (vous devrez créer une entité Invitation)
            $invitation = new Invitation();
            $invitation->setEmail($email);
            $invitation->setRole($role);
            $invitation->setToken($inviteToken);
            $invitation->setMessage($message);
            $invitation->setUsed(false);
            $invitation->setExpiresAt(new \DateTime('+7 days'));
            $invitation->setCreatedAt(new \DateTime());

            $em->persist($invitation);
            $em->flush();

            // Envoyer l'email
            $sent = $emailService->sendInvitation($email, $role, $message, $inviteToken);

            if ($sent) {
                return new JsonResponse(['success' => true, 'message' => 'Invitation envoyée avec succès']);
            } else {
                return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'envoi de l\'invitation'], 500);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/verify-invite/{token}', name: 'verifi_invite', methods: ['GET'])]
    public function verifyInvite(string $token, EntityManagerInterface $em): Response
    {
        $invitation = $em->getRepository(Invitation::class)->findOneBy(['token' => $token]);

        if (!$invitation) {
            return $this->redirect('https://google.com');
        }

        if ($invitation->isUsed() || $invitation->getExpiresAt() < new \DateTime()) {
            return $this->redirect('https://google.com');
        }

        // Marquer comme utilisé
        $invitation->setUsed(true);
        $em->flush();

        // Rediriger vers l'inscription
        return $this->redirect('http://172.17.61.95:8080/register?email=' . urlencode($invitation->getEmail()) . '&role=' . urlencode($invitation->getRole()));
    }

    #[Route('/{id}/role', name: 'update_user_role', methods: ['PUT'])]
    public function updateUserRole(int $id, Request $request, UserService $userService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $newRole = $data['role'] ?? null;

            if (!$newRole) {
                return new JsonResponse(['success' => false, 'message' => 'Le rôle est requis'], 400);
            }

            $result = $userService->updateUserRole($id, $newRole);

            if ($result['success']) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Rôle mis à jour avec succès',
                    'data' => $result['user']
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ], $result['code'] ?? 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur serveur lors de la modification du rôle'
            ], 500);
        }
    }

    #[Route('/{id}/status', name: 'update_user_status', methods: ['POST'])]
    public function updateUserStatus(int $id, Request $request, UserService $userService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $newStatus = $data['is_active'] ?? null;

            if ($newStatus === null) {
                return new JsonResponse(['success' => false, 'message' => 'Le statut is_active est requis'], 400);
            }

            $result = $userService->updateUserStatus($id, (bool)$newStatus);

            if ($result['success']) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Statut mis à jour avec succès',
                    'data' => $result['user']
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ], $result['code'] ?? 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur serveur lors de la modification du statut'
            ], 500);
        }
    }

    #[Route('/search', name: 'api_users_search', methods: ['GET'])]
    public function searchUsers(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('q', '');

            if (empty($query)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le paramètre de recherche "q" est requis'
                ], 400);
            }

            $users = $this->userService->searchUsers($query);

            return $this->json([
                'success' => true,
                'data' => $users,
                'count' => count($users),
                'query' => $query
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
