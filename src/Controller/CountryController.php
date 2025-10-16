<?php

namespace App\Controller;

use App\Entity\Country;
use App\Service\CountryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/countries', name: 'api_countries_')]
class CountryController extends AbstractController
{
    private CountryService $countryService;
    private ValidatorInterface $validator;

    public function __construct(
        CountryService $countryService,
        ValidatorInterface $validator
    ) {
        $this->countryService = $countryService;
        $this->validator = $validator;
    }

    /**
     * Récupérer tous les pays actifs
     */
    #[Route('/get', name: 'get_active', methods: ['GET'])]
    public function getActiveCountries(): JsonResponse
    {
        try {
            $countries = $this->countryService->getActiveCountries();

            $data = array_map(function (Country $country) {
                return $country->jsonSerialize();
            }, $countries);

            return $this->json([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des pays',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer un pays par ID
     */
    #[Route('/{id}', name: 'get_one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getCountry(int $id): JsonResponse
    {
        try {
            $country = $this->countryService->getCountryById($id);

            if (!$country) {
                return $this->json([
                    'success' => false,
                    'message' => 'Pays non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $country->jsonSerialize();

            return $this->json([
                'success' => true,
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du pays',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Créer un nouveau pays sur dienguix #tcheloooooooooo
     */
    #[Route('/create/country/dienguix', name: 'create', methods: ['POST'])]
    public function createCountry(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données JSON invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $country = $this->countryService->createCountry($data);

            // Validation
            $errors = $this->validator->validate($country);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return $this->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->countryService->saveCountry($country);

            $responseData = $country->jsonSerialize();

            return $this->json([
                'success' => true,
                'message' => 'Pays créé avec succès',
                'data' => $responseData
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création du pays',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Modifier un pays existant
     */
    #[Route('/edit/country/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateCountry(int $id, Request $request): JsonResponse
    {
        try {
            $country = $this->countryService->getCountryById($id);

            if (!$country) {
                return $this->json([
                    'success' => false,
                    'message' => 'Pays non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données JSON invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $updatedCountry = $this->countryService->updateCountry($country, $data);

            // Validation
            $errors = $this->validator->validate($updatedCountry);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return $this->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->countryService->saveCountry($updatedCountry);

            $responseData = $updatedCountry->jsonSerialize();

            return $this->json([
                'success' => true,
                'message' => 'Pays modifié avec succès',
                'data' => $responseData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du pays',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprimer un pays
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteCountry(int $id): JsonResponse
    {
        try {
            $country = $this->countryService->getCountryById($id);

            if (!$country) {
                return $this->json([
                    'success' => false,
                    'message' => 'Pays non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->countryService->deleteCountry($country);

            return $this->json([
                'success' => true,
                'message' => 'Pays supprimé avec succès'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du pays',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Désactiver/Activer un pays (soft delete)
     */
    #[Route('/{id}/toggle-active', name: 'toggle_active', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function toggleActive(int $id): JsonResponse
    {
        try {
            $country = $this->countryService->getCountryById($id);

            if (!$country) {
                return $this->json([
                    'success' => false,
                    'message' => 'Pays non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $updatedCountry = $this->countryService->toggleActiveStatus($country);

            $responseData = $updatedCountry->jsonSerialize();

            $action = $updatedCountry->isActive() ? 'activé' : 'désactivé';

            return $this->json([
                'success' => true,
                'message' => "Pays $action avec succès",
                'data' => $responseData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Récuperer toutes les informations d'un pays
     */
    #[Route('/stats', name: 'countries_stats', methods: ['GET'])]
    public function getCountriesStats(): JsonResponse
    {
        try {
            $stats = $this->countryService->getCountriesStatistics();

            return $this->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistiques par pays récupérées avec succès'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mettre à jour le pays de l'utilisateur connecté
     */
    #[Route('/update-my-country', name: 'update_user_country', methods: ['PUT'])]
    public function updateUserCountry(Request $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur connecté
            $user = $this->getUser();

            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données JSON invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['country_id'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'L\'ID du pays est requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $country = $this->countryService->getCountryById((int) $data['country_id']);

            if (!$country) {
                return $this->json([
                    'success' => false,
                    'message' => 'Pays non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            if (!$country->isActive()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Ce pays n\'est pas disponible'
                ], Response::HTTP_BAD_REQUEST);
            }

            $updatedUser = $this->countryService->updateUserCountry($user, $country);

            return $this->json([
                'success' => true,
                'message' => 'Pays mis à jour avec succès',
                'data' => [
                    'user_id' => $updatedUser->getId(),
                    'email' => $updatedUser->getEmail(),
                    'country' => [
                        'id' => $country->getId(),
                        'name' => $country->getName(),
                        'iso_code' => $country->getIsoCode(),
                        'currency_code' => $country->getCurrencyCode()
                    ]
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du pays',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
}
