<?php

namespace App\Service;

use App\Entity\Country;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

class CountryService
{
    private CountryRepository $countryRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CountryRepository $countryRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->countryRepository = $countryRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Récupérer tous les pays actifs
     *
     * @return Country[]
     */
    public function getActiveCountries(): array
    {
        return $this->countryRepository->findBy(['is_active' => true], ['name' => 'ASC']);
    }

    /**
     * Récupérer un pays par ID
     */
    public function getCountryById(int $id): ?Country
    {
        return $this->countryRepository->find($id);
    }

    /**
     * Récupérer tous les pays (actifs et inactifs)
     *
     * @return Country[]
     */
    public function getAllCountries(): array
    {
        return $this->countryRepository->findAll();
    }

    /**
     * Récupérer les pays par code ISO
     */
    public function getCountryByIsoCode(string $isoCode): ?Country
    {
        return $this->countryRepository->findOneBy(['iso_code' => $isoCode]);
    }

    /**
     * Créer un nouveau pays
     */
    public function createCountry(array $data): Country
    {
        $country = new Country();

        $this->populateCountryFromData($country, $data);

        // Définir les dates de création
        $now = new \DateTimeImmutable();
        $country->setCreatedAt($now);
        $country->setUpdatedAt(new \DateTime());

        return $country;
    }

    /**
     * Modifier un pays existant
     */
    public function updateCountry(Country $country, array $data): Country
    {
        $this->populateCountryFromData($country, $data);

        // Mettre à jour la date de modification
        $country->setUpdatedAt(new \DateTime());

        return $country;
    }

    /**
     * Sauvegarder un pays en base de données
     */
    public function saveCountry(Country $country): Country
    {
        $this->entityManager->persist($country);
        $this->entityManager->flush();

        return $country;
    }

    /**
     * Supprimer un pays définitivement
     */
    public function deleteCountry(Country $country): void
    {
        $this->entityManager->remove($country);
        $this->entityManager->flush();
    }

    /**
     * Basculer le statut actif d'un pays
     */
    public function toggleActiveStatus(Country $country): Country
    {
        $country->setIsActive(!$country->isActive());
        $country->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $country;
    }

    /**
     * Désactiver un pays (soft delete)
     */
    public function deactivateCountry(Country $country): Country
    {
        $country->setIsActive(false);
        $country->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $country;
    }

    /**
     * Activer un pays
     */
    public function activateCountry(Country $country): Country
    {
        $country->setIsActive(true);
        $country->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $country;
    }

    /**
     * Vérifier si un code ISO existe déjà
     */
    public function isIsoCodeExists(string $isoCode, ?int $excludeId = null): bool
    {
        $queryBuilder = $this->countryRepository->createQueryBuilder('c')
            ->where('c.iso_code = :isoCode')
            ->setParameter('isoCode', $isoCode);

        if ($excludeId) {
            $queryBuilder->andWhere('c.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult() !== null;
    }

    /**
     * Rechercher des pays par nom
     *
     * @return Country[]
     */
    public function searchCountriesByName(string $name, bool $activeOnly = true): array
    {
        $queryBuilder = $this->countryRepository->createQueryBuilder('c')
            ->where('c.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('c.name', 'ASC');

        if ($activeOnly) {
            $queryBuilder->andWhere('c.is_active = :active')
                ->setParameter('active', true);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Récupérer des pays par code de devise
     *
     * @return Country[]
     */
    public function getCountriesByCurrencyCode(string $currencyCode, bool $activeOnly = true): array
    {
        $criteria = ['currency_code' => $currencyCode];

        if ($activeOnly) {
            $criteria['is_active'] = true;
        }

        return $this->countryRepository->findBy($criteria, ['name' => 'ASC']);
    }

    /**
     * Compter le nombre de pays actifs
     */
    public function countActiveCountries(): int
    {
        return $this->countryRepository->count(['is_active' => true]);
    }

    /**
     * Remplir les données d'un pays à partir d'un tableau
     */
    private function populateCountryFromData(Country $country, array $data): void
    {
        if (isset($data['name'])) {
            $country->setName($data['name']);
        }

        if (isset($data['iso_code'])) {
            $country->setIsoCode(strtoupper($data['iso_code']));
        }

        if (isset($data['currency_code'])) {
            $country->setCurrencyCode(strtoupper($data['currency_code']));
        }

        if (isset($data['is_active'])) {
            $country->setIsActive((bool) $data['is_active']);
        }

        if (isset($data['status'])) {
            $country->setStatus((bool) $data['status']);
        }
    }

    public function getCountriesStatistics(): array
    {
        $countries = $this->entityManager->getRepository(Country::class)
            ->createQueryBuilder('c')
            ->where('c.is_active = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        $stats = [];

        foreach ($countries as $country) {
            $countryStats = $this->getCountryStatistics($country);
            $stats[] = $countryStats;
        }

        return $stats;
    }

    private function getCountryStatistics(Country $country): array
    {
        // Compter les utilisateurs actifs du pays
        $activeUsersCount = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.country = :country')
            ->andWhere('u.status = :status')
            ->setParameter('country', $country)
            ->setParameter('status', true)
            ->getQuery()
            ->getSingleScalarResult();

        // Montants envoyés depuis ce pays (from_country)
        $sentAmounts = $this->entityManager->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('SUM(t.amount_sent) as total_sent', 'COUNT(t.id) as sent_count')
            ->where('t.from_country = :country')
            ->andWhere('t.status = :status')
            ->setParameter('country', $country)
            ->setParameter('status', true)
            ->getQuery()
            ->getOneOrNullResult();

        // Montants reçus vers ce pays (to_country)
        $receivedAmounts = $this->entityManager->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('SUM(t.amount_received) as total_received', 'COUNT(t.id) as received_count')
            ->where('t.to_country = :country')
            ->andWhere('t.status = :status')
            ->setParameter('country', $country)
            ->setParameter('status', true)
            ->getQuery()
            ->getOneOrNullResult();

        // Nombre total de transactions (envoyées + reçues)
        $totalTransactions = ($sentAmounts['sent_count'] ?? 0) + ($receivedAmounts['received_count'] ?? 0);

        return [
            'country' => [
                'id' => $country->getId(),
                'name' => $country->getName(),
                'iso_code' => $country->getIsoCode(),
                'currency_code' => $country->getCurrencyCode()
            ],
            'users_count' => (int) $activeUsersCount,
            'transactions_stats' => [
                'total_amount_sent' => number_format((float) ($sentAmounts['total_sent'] ?? 0), 2),
                'total_amount_received' => number_format((float) ($receivedAmounts['total_received'] ?? 0), 2),
                'sent_count' => (int) ($sentAmounts['sent_count'] ?? 0),
                'received_count' => (int) ($receivedAmounts['received_count'] ?? 0),
                'total_transactions' => $totalTransactions
            ],
            'currency_info' => [
                'sent_currency' => $country->getCurrencyCode(),
                'received_currency' => $country->getCurrencyCode()
            ]
        ];
    }
}
