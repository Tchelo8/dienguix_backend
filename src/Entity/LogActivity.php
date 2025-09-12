<?php

namespace App\Entity;

use App\Repository\LogActivityRepository;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Entity(repositoryClass: LogActivityRepository::class)]
class LogActivity implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $date = null;

    #[ORM\ManyToOne(inversedBy: 'logActivities')]
    private ?User $uzer = null;

    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\Column(length: 255)]
    private ?string $categorie = null;

    #[ORM\Column(length: 255)]
    private ?string $ip_address = null;

    #[ORM\Column(length: 255)]
    private ?string $user_agent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $details = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getUzer(): ?User
    {
        return $this->uzer;
    }

    public function setUzer(?User $uzer): static
    {
        $this->uzer = $uzer;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function setUserAgent(string $user_agent): static
    {
        $this->user_agent = $user_agent;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d H:i:s'),
            'user' => $this->uzer,
            'action' => $this->action,
            'categorie' => $this->categorie,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'localisation' => $this->localisation,
            'details' => $this->details,
        ];
    }
}
