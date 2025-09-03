<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PasswordService
{
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }

    /**
     * Change user password with validation
     *
     * @param UserInterface $user
     * @param string $currentPassword
     * @param string $newPassword
     * @throws \InvalidArgumentException
     */
    public function changePassword(UserInterface $user, string $currentPassword, string $newPassword): void
    {
        // Vérifier que l'utilisateur implémente PasswordAuthenticatedUserInterface
        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw new \InvalidArgumentException('User must implement PasswordAuthenticatedUserInterface');
        }

        // Vérifier le mot de passe actuel
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new \InvalidArgumentException('Le mot de passe actuel entré n\'est pas correct ! Si vous réesayez trop le compte sera bloqué');
        }

        // Valider le nouveau mot de passe
        $this->validateNewPassword($newPassword);

        // Hasher le nouveau mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);

        // Mettre à jour le mot de passe
        $user->setPassword($hashedPassword);

        // Sauvegarder en base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Validate new password requirements
     *
     * @param string $password
     * @throws \InvalidArgumentException
     */
    private function validateNewPassword(string $password): void
    {
        // Contrainte : minimum 8 caractères
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit avoir au minimum 8 caractères ! ');
        }

        // possibiliter ajouter d'autres contraintes ici si nécessaire
        // Par exemple :
        // - Au moins une majuscule
        // - Au moins un chiffre
        // - Au moins un caractère spécial
        // etc.
    }

    /**
     * Generate a random password meeting requirements
     *
     * @param int $length
     * @return string
     */
    public function generateRandomPassword(int $length = 12): string
    {
        if ($length < 8) {
            $length = 8;
        }

        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }
}