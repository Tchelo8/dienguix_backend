<?php

namespace App\Controller;

use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class AuthController extends AbstractController
{
    private AuthService $authService;

    public function __construct(
        private UserService $userService, 
        AuthService $authService,
        private readonly MailerInterface $mailer,
    ) {
        $this->authService = $authService;
    }

    #[Route('/api/users/v1', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $roleId = $data['role'] ?? 1; 
            $countryId = $data['country'] ?? 1;
            $user = $this->userService->createUser($data, $roleId, $countryId);

            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole()->getName(),
                    'profile_created' => $user->getUserProfile() !== null,
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route('/api/auth/login/dgapp', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
      error_log('=== DEBUT LOGIN ===');
        
        $data = json_decode($request->getContent(), true);
        error_log('Data reçue: ' . json_encode($data));

        if (!isset($data['email']) || !isset($data['password'])) {
            error_log('Email ou password manquant');
            return $this->json([
                'success' => false,
                'error' => 'Email et mot de passe requis'
            ], 400);
        }
        
        try {
            error_log('Avant appel AuthService->initiateLogin');
            $result = $this->authService->initiateLogin($data['email'], $data['password']);
            error_log('Résultat AuthService: ' . json_encode($result));
            
            if (!$result['success']) {
                error_log('AuthService a échoué');
                return $this->json([
                    'success' => false,
                    'error' => $result['message']
                ], 422);
            }
            
            error_log('AuthService réussi, retour de la réponse');
            return $this->json([
                'success' => true,
                'message' => 'Code OTP envoyé par email',
                'otp_sent' => true
            ]);
        } catch (\Exception $e) {
            error_log('EXCEPTION dans login: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la connexion: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/auth/verify-otp/dgapp', name: 'api_verify_otp', methods: ['POST'])]
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['email']) || !isset($data['otp_code'])) {
            return $this->json([
                'success' => false,
                'error' => 'Email et code OTP requis'
            ], 400);
        }
        
        try {
            $result = $this->authService->verifyOtpAndLogin($data['email'], $data['otp_code']);
            if (!$result['success']) {
                return $this->json([
                    'success' => false,
                'error' => $result['message']
                ], 422);
            }
            return $this->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'token' => $result['token'],
                    'user' => $result['user']
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la vérification du code OTP' . $e->getMessage() 
            ], 500);
        }
    }

    #[Route('/api/test-email', name: 'test_email', methods: ['GET'])]
    public function testEmail(): JsonResponse
    {
        try {
            $this->mailer->send(
                (new Email())
                    ->from('Acme <onboarding@resend.dev>')
                    ->to('bessahenoc88@gmail.com')
                    ->subject('Test resend mail')
                    ->html('<strong>it works!</strong>')
            );

            return $this->json([
                'success' => true,
                'message' => 'Email de test envoyé avec succès!'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()
            ], 500);
        }
    }



    //  ROUTE DE TEST - Sans envoi d'email pour le mobile
    #[Route('/api/auth/test-login/dgapp', name: 'api_test_login', methods: ['POST'])]
    public function testLogin(Request $request): JsonResponse
    {
        error_log('=== DEBUT TEST LOGIN (sans email) ===');
        
        $data = json_decode($request->getContent(), true);
        error_log('Data reçue: ' . json_encode($data));

        if (!isset($data['email']) || !isset($data['password'])) {
            error_log('Email ou password manquant');
            return $this->json([
                'success' => false,
                'error' => 'Email et mot de passe requis'
            ], 400);
        }
        
        try {
            error_log('Avant appel AuthService->initiateTestLogin');
            $result = $this->authService->initiateTestLogin($data['email'], $data['password']);
            error_log('Résultat AuthService: ' . json_encode($result));
            
            if (!$result['success']) {
                error_log('AuthService a échoué');
                return $this->json([
                    'success' => false,
                    'error' => $result['message']
                ], 422);
            }
            
            error_log('AuthService réussi, retour de la réponse avec code OTP');
            return $this->json([
                'success' => true,
                'message' => 'Code OTP généré (mode test)',
                'otp_code' => $result['otp_code'], 
                'otp_generated' => true
            ]);
        } catch (\Exception $e) {
            error_log('EXCEPTION dans testLogin: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la connexion de test: ' . $e->getMessage()
            ], 500);
        }
    }
}