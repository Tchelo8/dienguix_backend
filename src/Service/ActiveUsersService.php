<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ActiveUsersService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    /**
     * Récupère les utilisateurs actifs avec leurs statistiques de transactions
     */
    public function getActiveUsersWithStats(): array
    {
        $query = $this->entityManager->createQuery('
            SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.last_login_at,
                u.created_at,
                c.name as country_name,
                c.currency_code,
                COALESCE(SUM(CASE WHEN ts.sender = u.id THEN ts.amount_sent ELSE 0 END), 0) as total_amount_sent,
                COALESCE(SUM(CASE WHEN tr.receiver = u.id THEN tr.amount_received ELSE 0 END), 0) as total_amount_received,
                COUNT(DISTINCT ts.id) + COUNT(DISTINCT tr.id) as transaction_count,
                COUNT(DISTINCT ts.id) as sent_count,
                COUNT(DISTINCT tr.id) as received_count
            FROM App\Entity\User u
            LEFT JOIN u.country c
            LEFT JOIN App\Entity\Transaction ts WITH ts.sender = u.id
            LEFT JOIN App\Entity\Transaction tr WITH tr.receiver = u.id
            WHERE u.is_active = true 
            AND u.status = true
            GROUP BY u.id, u.first_name, u.last_name, u.email, u.last_login_at, u.created_at, c.name, c.currency_code
            HAVING (COUNT(DISTINCT ts.id) + COUNT(DISTINCT tr.id)) > 0
            ORDER BY total_amount_sent DESC
        ');

        $results = $query->getResult();
        
        // Pour chaque utilisateur, récupérer le partenaire principal
        $usersWithStats = [];
        foreach ($results as $result) {
            $favoritePartner = $this->getFavoritePartner($result['id']);
            
            $usersWithStats[] = [
                'id' => $result['id'],
                'name' => $result['first_name'] . ' ' . $result['last_name'],
                'first_name' => $result['first_name'],
                'last_name' => $result['last_name'],
                'email' => $result['email'],
                'country' => $result['country_name'],
                'currency' => $result['currency_code'],
                'total_amount_sent' => number_format((float)$result['total_amount_sent'], 2),
                'total_amount_received' => number_format((float)$result['total_amount_received'], 2),
                'transaction_count' => (int)$result['transaction_count'],
                'sent_count' => (int)$result['sent_count'],
                'received_count' => (int)$result['received_count'],
                'average_amount' => $result['transaction_count'] > 0 
                    ? number_format(((float)$result['total_amount_sent'] + (float)$result['total_amount_received']) / (int)$result['transaction_count'], 2)
                    : '0.00',
                'last_login_at' => $result['last_login_at']?->format('Y-m-d H:i:s'),
                'created_at' => $result['created_at']?->format('Y-m-d H:i:s'),
                'favorite_partner' => $favoritePartner,
                'status' => 'active'
            ];
        }

        return $usersWithStats;
    }

    /**
     * Récupère le partenaire avec qui l'utilisateur a le plus de transactions
     */
    private function getFavoritePartner(int $userId): ?array
    {
        // Récupérer le destinataire le plus fréquent pour les envois
        $sentQuery = $this->entityManager->createQuery('
            SELECT 
                r.id,
                r.first_name,
                r.last_name,
                rc.name as country_name,
                COUNT(t.id) as transaction_count,
                SUM(t.amount_sent) as total_amount
            FROM App\Entity\Transaction t
            JOIN t.receiver r
            LEFT JOIN r.country rc
            WHERE t.sender = :userId
            AND t.status = true
            GROUP BY r.id, r.first_name, r.last_name, rc.name
            ORDER BY transaction_count DESC, total_amount DESC
        ');
        $sentQuery->setParameter('userId', $userId);
        $sentQuery->setMaxResults(1);
        
        $sentResult = $sentQuery->getOneOrNullResult();

        // Récupérer l'expéditeur le plus fréquent pour les réceptions
        $receivedQuery = $this->entityManager->createQuery('
            SELECT 
                s.id,
                s.first_name,
                s.last_name,
                sc.name as country_name,
                COUNT(t.id) as transaction_count,
                SUM(t.amount_received) as total_amount
            FROM App\Entity\Transaction t
            JOIN t.sender s
            LEFT JOIN s.country sc
            WHERE t.receiver = :userId
            AND t.status = true
            GROUP BY s.id, s.first_name, s.last_name, sc.name
            ORDER BY transaction_count DESC, total_amount DESC
        ');
        $receivedQuery->setParameter('userId', $userId);
        $receivedQuery->setMaxResults(1);
        
        $receivedResult = $receivedQuery->getOneOrNullResult();

        // Déterminer le partenaire principal (celui avec le plus de transactions)
        $favoritePartner = null;
        
        if ($sentResult && $receivedResult) {
            if ($sentResult['transaction_count'] >= $receivedResult['transaction_count']) {
                $favoritePartner = $sentResult;
                $favoritePartner['relationship_type'] = 'sent_to';
            } else {
                $favoritePartner = $receivedResult;
                $favoritePartner['relationship_type'] = 'received_from';
            }
        } elseif ($sentResult) {
            $favoritePartner = $sentResult;
            $favoritePartner['relationship_type'] = 'sent_to';
        } elseif ($receivedResult) {
            $favoritePartner = $receivedResult;
            $favoritePartner['relationship_type'] = 'received_from';
        }

        if ($favoritePartner) {
            return [
                'id' => $favoritePartner['id'],
                'name' => $favoritePartner['first_name'] . ' ' . $favoritePartner['last_name'],
                'country' => $favoritePartner['country_name'],
                'transaction_count' => (int)$favoritePartner['transaction_count'],
                'total_amount' => number_format((float)$favoritePartner['total_amount'], 2),
                'relationship_type' => $favoritePartner['relationship_type']
            ];
        }

        return null;
    }

    /**
     * Récupère les top envoyeurs
     */
    public function getTopSenders(int $limit = 10): array
    {
        $users = $this->getActiveUsersWithStats();
        
        // Trier par montant envoyé et prendre les premiers
        usort($users, function($a, $b) {
            return (float)str_replace(',', '', $b['total_amount_sent']) <=> (float)str_replace(',', '', $a['total_amount_sent']);
        });

        return array_slice($users, 0, $limit);
    }

    /**
     * Récupère les top destinataires
     */
    public function getTopReceivers(int $limit = 10): array
    {
        $users = $this->getActiveUsersWithStats();
        
        // Trier par montant reçu et prendre les premiers
        usort($users, function($a, $b) {
            return (float)str_replace(',', '', $b['total_amount_received']) <=> (float)str_replace(',', '', $a['total_amount_received']);
        });

        return array_slice($users, 0, $limit);
    }

    /**
     * Filtre les utilisateurs par pays
     */
    public function getActiveUsersByCountry(string $countryName): array
    {
        $users = $this->getActiveUsersWithStats();
        
        return array_filter($users, function($user) use ($countryName) {
            return strtolower($user['country']) === strtolower($countryName);
        });
    }

    /**
     * Recherche d'utilisateurs par terme
     */
    public function searchActiveUsers(string $searchTerm): array
    {
        $users = $this->getActiveUsersWithStats();
        $searchTerm = strtolower($searchTerm);
        
        return array_filter($users, function($user) use ($searchTerm) {
            return strpos(strtolower($user['name']), $searchTerm) !== false ||
                   strpos(strtolower($user['email']), $searchTerm) !== false ||
                   strpos(strtolower($user['country']), $searchTerm) !== false;
        });
    }
}