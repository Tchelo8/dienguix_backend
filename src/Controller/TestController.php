<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController
{
    #[Route('/', name: 'app_test')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Bienvenu sur l\'API de DIENGUIX dévéloppé par Henoc Tchelo !',
        ]);
    }
}
