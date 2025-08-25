<?php

namespace App\Controller;

use App\Entity\Role;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RoleController extends AbstractController
{
    #[Route('api/role/listing', name: 'app_role')]
    public function index(ManagerRegistry $doctrine): JsonResponse
    {
         try {
        $roles = $doctrine->getRepository(Role::class)->findBy(['is_active' => true]);
        $roleData = [];
        
        foreach ($roles as $role) {
            $roleData[] = $role->jsonSerialize();
        }

        return $this->json([
            'success' => true,
            'data' => $roleData,
            'message' => 'Rôles récupérés avec succès'
        ]);
        
    } catch (\Throwable $e) {
        return $this->json([
            'success' => false,
            'error' => 'Erreur lors de la récupération des rôles présent dans la plateforme',
            'message' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    }
}
