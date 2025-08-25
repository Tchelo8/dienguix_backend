<?php
// src/Service/OtpService.php

namespace App\Service;

use App\Entity\CodeOtp;
use Doctrine\ORM\EntityManagerInterface;

class OtpService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function generateOtpCode(string $email): string
    {
        // Générer un code à 6 chiffres
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Créer l'entité CodeOtp
        $codeOtp = new CodeOtp();
        $codeOtp->setEmail($email);
        $codeOtp->setCode($otpCode);
        $codeOtp->setExpiredAt(new \DateTimeImmutable('+10 minutes')); // Expire dans 10 minutes
        $codeOtp->setIsUsed(false);

        $this->entityManager->persist($codeOtp);
        $this->entityManager->flush();

        return $otpCode;
    }

    public function verifyOtpCode(string $email, string $otpCode): bool
    {
        $codeOtp = $this->entityManager->getRepository(CodeOtp::class)->findOneBy([
            'email' => $email,
            'code' => $otpCode,
            'is_used' => false
        ]);

        if (!$codeOtp) {
            return false;
        }

        // Vérifier si le code n'a pas expiré
        if ($codeOtp->getExpiredAt() < new \DateTime()) {
            return false;
        }

        return true;
    }

    public function markOtpAsUsed(string $email, string $otpCode): void
    {
        $codeOtp = $this->entityManager->getRepository(CodeOtp::class)->findOneBy([
            'email' => $email,
            'code' => $otpCode,
            'is_used' => false
        ]);

        if ($codeOtp) {
            $codeOtp->setIsUsed(true);
            $this->entityManager->flush();
        }
    }

    public function invalidateOldCodes(string $email): void
    {
        // CORRECTION : Utiliser DQL au lieu de Query Builder pour PostgreSQL
        $dql = 'UPDATE App\Entity\CodeOtp c SET c.is_used = :used WHERE c.email = :email AND c.is_used = :notUsed';
        
        $this->entityManager->createQuery($dql)
            ->setParameter('used', true)
            ->setParameter('email', $email)
            ->setParameter('notUsed', false)
            ->execute();
    }
}