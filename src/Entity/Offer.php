<?php

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'offers')]
    #[Assert\NotNull(message: 'Bitte einen Kunden auswählen.')]
    private ?Customer $customer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $textarea = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'Bitte ein Angebotsdatum angeben.')]
    private ?\DateTimeInterface $offerDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $termDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created = null;

    #[ORM\ManyToOne(inversedBy: 'offers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToOne(mappedBy: 'offer', cascade: ['persist', 'remove'])]
    private ?Task $task = null;

    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: TaskDraw::class)]
    private Collection $taskDraws;

    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: TaskImage::class)]
    private Collection $taskImages;

    public function __construct()
    {
        $this->taskDraws = new ArrayCollection();
        $this->taskImages = new ArrayCollection();
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

    public function getOfferDate(): ?\DateTimeInterface
    {
        return $this->offerDate;
    }

    public function setOfferDate(\DateTimeInterface $offerDate): self
    {
        $this->offerDate = $offerDate;

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

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
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

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): self
    {
        // unset the owning side of the relation if necessary
        if ($task === null && $this->task !== null) {
            $this->task->setOffer(null);
        }

        // set the owning side of the relation if necessary
        if ($task !== null && $task->getOffer() !== $this) {
            $task->setOffer($this);
        }

        $this->task = $task;

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
            $taskDraw->setOffer($this);
        }

        return $this;
    }

    public function removeTaskDraw(TaskDraw $taskDraw): self
    {
        if ($this->taskDraws->removeElement($taskDraw)) {
            // set the owning side to null (unless already changed)
            if ($taskDraw->getOffer() === $this) {
                $taskDraw->setOffer(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getId() . ', (' . $this->customer->getSurname() . ', ' . $this->customer->getFirstname().' '. $this->getCustomer()->getPlz() . ', ' . $this->getCustomer()->getCity() .')';
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
            $taskImage->setOffer($this);
        }

        return $this;
    }

    public function removeTaskImage(TaskImage $taskImage): self
    {
        if ($this->taskImages->removeElement($taskImage)) {
            // set the owning side to null (unless already changed)
            if ($taskImage->getOffer() === $this) {
                $taskImage->setOffer(null);
            }
        }

        return $this;
    }
}
