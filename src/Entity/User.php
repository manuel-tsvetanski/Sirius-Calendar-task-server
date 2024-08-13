<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $email;

    #[ORM\Column(type: 'string', length: 20)]
    private $phone;

    // Getter for id
    public function getId(): ?int
    {
        return $this->id;
    }

    // Getter for name
    public function getName(): ?string
    {
        return $this->name;
    }

    // Setter for name
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    // Getter for email
    public function getEmail(): ?string
    {
        return $this->email;
    }

    // Setter for email
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    // Getter for phone
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    // Setter for phone
    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }
}


