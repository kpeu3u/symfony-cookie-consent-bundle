<?php

namespace CookieConsentBundle\Ui;

use Symfony\Component\Form\FormView;

class ConsentFormDto
{
    public function __construct(
        public readonly FormView    $simpleForm,
        public readonly FormView    $detailedForm,
        public readonly string      $position,
        public readonly string|null $readMoreRoute,
        public readonly string $theme = 'light',
        public readonly array $necessaryCookies = []
    )
    {
    }

    public function toArray(): array
    {
        return [
            'simple_form' => $this->simpleForm,
            'detailed_form' => $this->detailedForm,
            'position' => $this->position,
            'theme' => $this->theme,
            'necessary_cookies' => $this->necessaryCookies,
            'read_more_route' => $this->readMoreRoute,
        ];
    }
}