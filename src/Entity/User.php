<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255)]
    private ?string $last_name = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private ?bool $is_active = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTime $updated_at = null;

    #[ORM\Column]
    private ?\DateTime $last_login_at = null;

    #[ORM\OneToOne(mappedBy: 'uzer', cascade: ['persist', 'remove'])]
    private ?UserProfile $userProfile = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Country $country = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Role $role = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'sender')]
    private Collection $transaction_sender;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'receiver')]
    private Collection $transaction_receiver;

    /**
     * @var Collection<int, LogActivity>
     */
    #[ORM\OneToMany(targetEntity: LogActivity::class, mappedBy: 'uzer')]
    private Collection $logActivities;

    public function __construct()
    {
        $this->transaction_sender = new ArrayCollection();
        $this->transaction_receiver = new ArrayCollection();
        $this->logActivities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTime
    {
        return $this->last_login_at;
    }

    public function setLastLoginAt(\DateTime $last_login_at): static
    {
        $this->last_login_at = $last_login_at;

        return $this;
    }

    public function getUserProfile(): ?UserProfile
    {
        return $this->userProfile;
    }

    public function setUserProfile(?UserProfile $userProfile): static
    {
        // unset the owning side of the relation if necessary
        if ($userProfile === null && $this->userProfile !== null) {
            $this->userProfile->setUzer(null);
        }

        // set the owning side of the relation if necessary
        if ($userProfile !== null && $userProfile->getUzer() !== $this) {
            $userProfile->setUzer($this);
        }

        $this->userProfile = $userProfile;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }
    public function getRoles(): array
    {
        // Retourne un tableau de rôles 
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // Supprime les données sensibles temporaires (ex: mot de passe en clair)
        // Ne  rien faire ici, sauf si  besoin
    }

    public function getUserIdentifier(): string
    {
        // Retourne une propriété unique de l'utilisateur, ex: email ou username
        return $this->email; // remplace $this->email par le champ identifiant
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            // Relations - avec vérification pour éviter les références circulaires
            'country' => $this->country ? [
                'id' => $this->country->getId(),
                'name' => $this->country->getName(), 
            ] : null,
            
            'role' => $this->role ? [
                'id' => $this->role->getId(),
                'name' => $this->role->getName(), 
            ] : null,
            'user_profile' => $this->userProfile?->jsonSerialize(),
        ];
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionSender(): Collection
    {
        return $this->transaction_sender;
    }

    public function addTransactionSender(Transaction $transactionSender): static
    {
        if (!$this->transaction_sender->contains($transactionSender)) {
            $this->transaction_sender->add($transactionSender);
            $transactionSender->setSender($this);
        }

        return $this;
    }

    public function removeTransactionSender(Transaction $transactionSender): static
    {
        if ($this->transaction_sender->removeElement($transactionSender)) {
            // set the owning side to null (unless already changed)
            if ($transactionSender->getSender() === $this) {
                $transactionSender->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionReceiver(): Collection
    {
        return $this->transaction_receiver;
    }

    public function addTransactionReceiver(Transaction $transactionReceiver): static
    {
        if (!$this->transaction_receiver->contains($transactionReceiver)) {
            $this->transaction_receiver->add($transactionReceiver);
            $transactionReceiver->setReceiver($this);
        }

        return $this;
    }

    public function removeTransactionReceiver(Transaction $transactionReceiver): static
    {
        if ($this->transaction_receiver->removeElement($transactionReceiver)) {
            // set the owning side to null (unless already changed)
            if ($transactionReceiver->getReceiver() === $this) {
                $transactionReceiver->setReceiver(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogActivity>
     */
    public function getLogActivities(): Collection
    {
        return $this->logActivities;
    }

    public function addLogActivity(LogActivity $logActivity): static
    {
        if (!$this->logActivities->contains($logActivity)) {
            $this->logActivities->add($logActivity);
            $logActivity->setUzer($this);
        }

        return $this;
    }

    public function removeLogActivity(LogActivity $logActivity): static
    {
        if ($this->logActivities->removeElement($logActivity)) {
            // set the owning side to null (unless already changed)
            if ($logActivity->getUzer() === $this) {
                $logActivity->setUzer(null);
            }
        }

        return $this;
    }
}
