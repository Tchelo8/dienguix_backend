<?php

namespace App\Controller;

use App\Service\PasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityController extends AbstractController
{
    private PasswordService $passwordService;

    public function __construct(PasswordService $passwordService)
    {
        $this->passwordService = $passwordService;
    }


    #[Route('user/security/change-password', name: 'app_change_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non connecté'
            ], 401);
        }
        $data = json_decode($request->getContent(), true);
        if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
            return $this->json([
                'success' => false,
                'error' => 'Le mot de passe actuel et le nouveau mot de passe sont requis !'
            ], 400);
        }
        try {
            $this->passwordService->changePassword(
                $user,
                $data['currentPassword'],
                $data['newPassword']
            ); 
            return $this->json([
                'success' => true,
                'message' => 'Mot de passe bien changé ! Gardez le bien !',
                'data' => [
                    'message' => 'Mot de passe bien changé ! Gardez le bien !'
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue pendant le changement du mot de passe !'
            ], 500);
        }
    }
}