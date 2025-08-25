<?php
// src/Service/AuthService.php

namespace App\Service;

use App\Entity\User;
use App\Entity\CodeOtp;
use App\Service\OtpService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthService
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;
    private OtpService $otpService;
    private EmailService $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        OtpService $otpService,
        EmailService $emailService
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
        $this->otpService = $otpService;
        $this->emailService = $emailService;
    }

    public function initiateLogin(string $email, string $password): array
    {
        // Vérifier les identifiants
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return [
                'success' => false,
                'message' => 'Identifiants invalides'
            ];
        }

        // D'abord invalider les anciens codes OTP pour cet email
        $this->otpService->invalidateOldCodes($email);

        // Ensuite générer un nouveau code OTP
        $otpCode = $this->otpService->generateOtpCode($email);

        // Envoyer le code par email
        $emailSent = $this->emailService->sendOtpCode($email, $otpCode);

        if (!$emailSent) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du code OTP'
            ];
        }

        return [
            'success' => true,
            'message' => 'Un code OTP vous a été envoyé par mail, allez y regarder vos mails.'
        ];
    }

    public function verifyOtpAndLogin(string $email, string $otpCode): array
    {
        // Vérifier le code OTP
        if (!$this->otpService->verifyOtpCode($email, $otpCode)) {
            return [
                'success' => false,
                'message' => 'Code OTP invalide ou expiré'
            ];
        }

        // Récupérer l'utilisateur
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ];
        }

        // Marquer le code OTP comme utilisé
        $this->otpService->markOtpAsUsed($email, $otpCode);

        // Générer le token JWT
        $token = $this->jwtManager->create($user);

        return [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]
        ];
    }
}