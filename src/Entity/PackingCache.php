<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stores cached packing results to avoid redundant API calls.
 *
 * Each row represents a unique combination of products and available boxes.
 * A null packaging reference means no suitable box was found for that input.
 */
#[ORM\Entity]
#[ORM\Table(name: 'packing_cache')]
class PackingCache
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    private string $requestHash;

    #[ORM\ManyToOne(targetEntity: Packaging::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Packaging $packaging;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(string $requestHash, ?Packaging $packaging)
    {
        $this->requestHash = $requestHash;
        $this->packaging = $packaging;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestHash(): string
    {
        return $this->requestHash;
    }

    public function getPackaging(): ?Packaging
    {
        return $this->packaging;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
