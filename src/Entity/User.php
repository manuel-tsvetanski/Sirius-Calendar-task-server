<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as CustomAssert;

#[ORM\Entity]
class User {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Name is required")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Name cannot be longer than {{ limit }} characters"
    )]
    private $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(
        message: "Please enter a valid email address: {{ value }}",
        mode: 'strict', // Strict mode according to RFC 5322,
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/",
        message: "Please enter a valid email address: {{ value }}"
    )]
    #[CustomAssert\ValidEmailDomain]
    private $email;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: "Phone number is required")]
    #[Assert\Regex(
        pattern: "/^\+?[0-9\s\-]+$/",
        message: "Please enter a valid phone number"
    )]
    private $phone;

    // Getter for id
    public function getId(): ?int {
        return $this->id;
    }

    // Getter for name
    public function getName(): ?string {
        return $this->name;
    }

    // Setter for name
    public function setName(string $name): self {
        $this->name = $name;

        return $this;
    }

    // Getter for email
    public function getEmail(): ?string {
        return $this->email;
    }

    // Setter for email
    public function setEmail(string $email): self {
        $this->email = $email;

        return $this;
    }

    // Getter for phone
    public function getPhone(): ?string {
        return $this->phone;
    }

    // Setter for phone
    public function setPhone(string $phone): self {
        $this->phone = $phone;

        return $this;
    }
}
