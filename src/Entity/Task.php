<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?Customer $customer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $textarea = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $termDate = null;

    #[ORM\OneToOne(inversedBy: 'task', cascade: ['persist', 'remove'])]
    private ?Offer $offer = null;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskImage::class)]
    private Collection $taskImages;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskDraw::class)]
    private Collection $taskDraws;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->taskImages = new ArrayCollection();
        $this->taskDraws = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getTextarea(): ?string
    {
        return $this->textarea;
    }

    public function setTextarea(?string $textarea): self
    {
        $this->textarea = $textarea;

        return $this;
    }

    public function getTaskDate(): ?\DateTimeInterface
    {
        return $this->taskDate;
    }

    public function setTaskDate(?\DateTimeInterface $taskDate): self
    {
        $this->taskDate = $taskDate;

        return $this;
    }

    public function getTermDate(): ?\DateTimeInterface
    {
        return $this->termDate;
    }

    public function setTermDate(?\DateTimeInterface $termDate): self
    {
        $this->termDate = $termDate;

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

    /**
     * @return Collection<int, TaskImage>
     */
    public function getTaskImages(): Collection
    {
        return $this->taskImages;
    }

    public function addTaskImage(TaskImage $taskImage): self
    {
        if (!$this->taskImages->contains($taskImage)) {
            $this->taskImages->add($taskImage);
            $taskImage->setTask($this);
        }

        return $this;
    }

    public function removeTaskImage(TaskImage $taskImage): self
    {
        if ($this->taskImages->removeElement($taskImage)) {
            // set the owning side to null (unless already changed)
            if ($taskImage->getTask() === $this) {
                $taskImage->setTask(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TaskDraw>
     */
    public function getTaskDraws(): Collection
    {
        return $this->taskDraws;
    }

    public function addTaskDraw(TaskDraw $taskDraw): self
    {
        if (!$this->taskDraws->contains($taskDraw)) {
            $this->taskDraws->add($taskDraw);
            $taskDraw->setTask($this);
        }

        return $this;
    }

    public function removeTaskDraw(TaskDraw $taskDraw): self
    {
        if ($this->taskDraws->removeElement($taskDraw)) {
            // set the owning side to null (unless already changed)
            if ($taskDraw->getTask() === $this) {
                $taskDraw->setTask(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->customer->getId() . ', ' . $this->customer->getSurname() . ', ' . $this->customer->getFirstname().' ('. $this->getCustomer()->getPlz() . ', ' . $this->getCustomer()->getCity() .')';
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
