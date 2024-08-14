<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Appointment {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'date')]
    private $date;

    #[ORM\Column(type: 'time')]
    private $time;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"])]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    // Getter for id
    public function getId(): ?int {
        return $this->id;
    }

    // Getter for date
    public function getDate(): ?\DateTimeInterface {
        return $this->date;
    }

    // Setter for date
    public function setDate(\DateTime $date): self {
        $this->date = $date; // or whatever field name is appropriate
        return $this;
    }

    // Getter for time
    public function getTime(): ?\DateTimeInterface {
        return $this->time;
    }

    // Setter for time
    public function setTime(\DateTimeInterface $time): self {
        $this->time = $time;

        return $this;
    }

    // Getter for user
    public function getUser(): ?User {
        return $this->user;
    }

    // Setter for user
    public function setUser(User $user): self {
        $this->user = $user;

        return $this;
    }
}

