<?php

namespace App\Entity;

use App\Repository\TaskDrawRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskDrawRepository::class)]
class TaskDraw
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $path = null;

    #[ORM\ManyToOne(inversedBy: 'taskDraws')]
    private ?Task $task = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $base64Data = null;

    #[ORM\ManyToOne(inversedBy: 'taskDraws')]
    private ?Offer $offer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function getBase64Data(): ?string
    {
        return $this->base64Data;
    }

    public function setBase64Data(?string $base64Data): self
    {
        $this->base64Data = $base64Data;

        return $this;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(?Offer $offer): self
    {
        $this->offer = $offer;

        return $this;
    }
}
