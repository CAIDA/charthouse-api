<?php

namespace App\Entity\Expression;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Expression\ExpressionFunctionTagRepository")
 */
class ExpressionFunctionTag
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Expression\ExpressionFunction", mappedBy="tags")
     */
    private $functions;

    public function __construct()
    {
        $this->functions = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|ExpressionFunction[]
     */
    public function getFunctions(): Collection
    {
        return $this->functions;
    }

    public function addFunction(ExpressionFunction $function): self
    {
        if (!$this->functions->contains($function)) {
            $this->functions[] = $function;
            $function->addTag($this);
        }

        return $this;
    }

    public function removeFunction(ExpressionFunction $function): self
    {
        if ($this->functions->contains($function)) {
            $this->functions->removeElement($function);
            $function->removeTag($this);
        }

        return $this;
    }
}
