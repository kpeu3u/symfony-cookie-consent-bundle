<?php

declare(strict_types=1);

/*
 * This file is part of the CookieConsentBundle Symfony bundle.
 * Created by Radoslav Georgiev.
 */

namespace CookieConsentBundle\Form;

class ConsentVendorTypeModel
{
    private string $name;
    private bool $consentGiven;
    private string $descriptionKey;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getConsentGiven(): bool
    {
        return $this->consentGiven;
    }

    public function setConsentGiven(bool $consentGiven): void
    {
        $this->consentGiven = $consentGiven;
    }

    public function getDescriptionKey(): string
    {
        return $this->descriptionKey;
    }

    public function setDescriptionKey(string $descriptionKey): void
    {
        $this->descriptionKey = $descriptionKey;
    }
}
