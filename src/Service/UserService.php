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
}
