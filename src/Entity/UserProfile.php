<?php

namespace App\Entity;

use App\Repository\UserProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
class UserProfile implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userProfile', cascade: ['persist', 'remove'])]
    private ?User $uzer = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column]
    private ?\DateTime $birth_date = null;

    #[ORM\Column]
    private ?bool $verified = null;

    #[ORM\Column(length: 255)]
    private ?string $gender = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTime $updated_at = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\ManyToOne(inversedBy: 'userProfiles')]
    private ?Document $document = null;

    #[ORM\Column(length: 255)]
    private ?string $document_number = null;

    #[ORM\Column(length: 255)]
    private ?string $document_file = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getBirthDate(): ?\DateTime
    {
        return $this->birth_date;
    }

    public function setBirthDate(\DateTime $birth_date): static
    {
        $this->birth_date = $birth_date;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

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

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->document_number;
    }

    public function setDocumentNumber(string $document_number): static
    {
        $this->document_number = $document_number;

        return $this;
    }

    public function getDocumentFile(): ?string
    {
        return $this->document_file;
    }

    public function setDocumentFile(string $document_file): static
    {
        $this->document_file = $document_file;

        return $this;
    }

        public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'address' => $this->address,
            'photo' => $this->photo,
            'city' => $this->city,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'verified' => $this->verified,
            'gender' => $this->gender,
            'status' => $this->status,
            'document_number' => $this->document_number,
            'document_file' => $this->document_file,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            // Document associé (sans référence circulaire)
            'document' => $this->document ? [
                'id' => $this->document->getId(),
                'name' => $this->document->getName(), 

            ] : null,
        ];
    }

}
