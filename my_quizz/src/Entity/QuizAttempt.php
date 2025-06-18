<?php

namespace App\Entity;

use App\Repository\QuizAttemptRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizAttemptRepository::class)]
class QuizAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'quizAttempts')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class)]
    #[ORM\JoinColumn(name: 'categorie_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Categorie $categorie = null;

    #[ORM\Column(type: 'integer')]
    private int $score;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $attemptedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUser(?User $user): self    
    {
         $this->user = $user;
         return $this;
    }
   
    public function getUser(): ?User 
    {
        return $this->user;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }
    
    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setScore($score ): self
    {
        $this->score = $score;
        return $this;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setAttemptedAt($attemptedAt): self
    {
        $this->attemptedAt = $attemptedAt;
        return $this;
    }

    public function getAttemptedAt()
    {
        return $this->attemptedAt;
    }






}
