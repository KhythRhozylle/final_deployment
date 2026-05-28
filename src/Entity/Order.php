<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['order:read']],
    denormalizationContext: ['groups' => ['order:write']]
)]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?Customer $customer = null;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $productName = null;

    #[ORM\Column]
    #[Groups(['order:read', 'order:write'])]
    private ?float $quantity = null;

    #[ORM\Column]
    #[Groups(['order:read', 'order:write'])]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $status = null;

    #[ORM\Column(length: 36, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $orderGroupId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $notes = null;

    #[ORM\Column(length: 20, options: ['default' => 'staff'])]
    #[Groups(['order:read'])]
    private string $source = 'staff';

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?int $productId = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['order:read'])]
    private bool $stockDeducted = false;

    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?\DateTime $orderDate = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $paymentStatus = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $paymentProofPath = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $referenceNumber = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->orderDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getOrderGroupId(): ?string
    {
        return $this->orderGroupId;
    }

    public function setOrderGroupId(?string $orderGroupId): static
    {
        $this->orderGroupId = $orderGroupId;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    public function isStockDeducted(): bool
    {
        return $this->stockDeducted;
    }

    public function setStockDeducted(bool $stockDeducted): static
    {
        $this->stockDeducted = $stockDeducted;

        return $this;
    }

    public function getOrderDate(): ?\DateTime
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTime $orderDate): static
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getPaymentProofPath(): ?string
    {
        return $this->paymentProofPath;
    }

    public function setPaymentProofPath(?string $paymentProofPath): static
    {
        $this->paymentProofPath = $paymentProofPath;

        return $this;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(?string $referenceNumber): static
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
