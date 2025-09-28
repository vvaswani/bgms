<?php
namespace App\Entity;

use Symfony\Component\Serializer\Annotation\SerializedName;
use App\Repository\ReadingRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: ReadingRepository::class)]
class Reading
{

    public const TYPE_FASTING = 'fasting';
    public const TYPE_POST_PRANDIAL = 'post-prandial';
    public const TYPE_RANDOM = 'random';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'float')]
    private ?float $value = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => self::TYPE_RANDOM])]
    private string $type = self::TYPE_RANDOM;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(?float $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $allowedTypes = [
            self::TYPE_FASTING,
            self::TYPE_POST_PRANDIAL,
            self::TYPE_RANDOM,
        ];

        if (!in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException("Invalid reading type");
        }

        $this->type = $type;
        return $this;
    }
}
