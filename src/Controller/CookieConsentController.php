<?php

declare(strict_types=1);

namespace CookieConsentBundle\Controller;

use Exception;
use CookieConsentBundle\Enum\FormSubmitName;
use CookieConsentBundle\Form\ConsentDetailedType;
use CookieConsentBundle\Form\ConsentSimpleType;
use CookieConsentBundle\Service\CookieConsentService;
use CookieConsentBundle\Ui\ConsentFormDto;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
class CookieConsentController
{
    public function __construct(
        private readonly Environment          $twigEnvironment,
        private readonly FormFactoryInterface $formFactory,
        private readonly RouterInterface      $router,
        private readonly LocaleAwareInterface $translator,
        private readonly string|null          $formAction,
        private readonly string|null          $readMoreRoute,
        private readonly CookieConsentService $cookieConsentService,
        private readonly string               $position,
        private readonly RequestStack         $requestStack,
        private readonly LoggerInterface      $logger,
        private readonly string               $theme = 'light'
    )
    {
    }

    #[Route('/cookie-consent/update', name: 'cookie_consent.update')]
    public function update(): Response
    {
        $request = $this->getCurrentRequest();

        if ($request->getMethod() != Request::METHOD_POST) {
            throw new MethodNotAllowedHttpException([Request::METHOD_POST]);
        }

        $form = $this->getForm($request);
        if ($form === null) {
            return new JsonResponse('error', Response::HTTP_BAD_REQUEST);
        }
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid() || $form->getClickedButton() === null) {
            return new JsonResponse('error', Response::HTTP_BAD_REQUEST);
        }

        try {
            $headers = match ($form->getClickedButton()->getName()) {
                FormSubmitName::REJECT_ALL => $this->cookieConsentService->rejectAllCookies($request),
                FormSubmitName::ACCEPT_ALL => $this->cookieConsentService->acceptAllCookies($request),
                FormSubmitName::SAVE_CONSENT_SETTINGS => $this->cookieConsentService->saveConsentSettings($form->getData(), $request),
            };
            $response = new JsonResponse('ok', Response::HTTP_CREATED);
            foreach ($headers->getCookies() as $cookie) {
                $response->headers->setCookie($cookie);
            }
            return $response;
        } catch (Exception $exception) {
            $this->logger->error('Unable to save cookie consent.', ['exception' => $exception]);
            return new JsonResponse('error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return Request|null
     */
    public function getCurrentRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private function getForm(Request $request): ?FormInterface
    {
        if ($request->request->has('consent_simple') && !$request->request->has('consent_detailed')) {
            return $this->createSimpleConsentForm();
        } else if ($request->request->has('consent_detailed') && !$request->request->has('consent_simple')) {
            return $this->createDetailedConsentForm();
        }

        return null;
    }

    /**
     * Create cookie consent form.
     */
    private function createSimpleConsentForm(): FormInterface
    {
        $formBuilder = $this->formFactory->createBuilder(ConsentSimpleType::class);

        if ($this->formAction != null) {
            $formBuilder->setAction($this->router->generate($this->formAction));
        }

        return $formBuilder->getForm();
    }

    private function createDetailedConsentForm(): FormInterface
    {
        $formModel = $this->cookieConsentService->createDetailedFormForRequest($this->getCurrentRequest());

        $formBuilder = $this->formFactory->createBuilder(ConsentDetailedType::class, $formModel);

        if ($this->formAction != null) {
            $formBuilder->setAction($this->router->generate($this->formAction));
        }

        return $formBuilder->getForm();
    }

    /**
     * Show cookie consent.
     */
    #[Route('/cookie-consent/view-if-no-consent', name: 'cookie_consent.view_if_no_consent')]
    public function viewIfNoConsent(): Response
    {
        if ($this->cookieConsentService->isCookieConsentFormSubmittedByUser($this->getCurrentRequest()) === false) {
            return $this->view();
        }

        $response = new Response();
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');
        return $response;
    }

    /**
     * Show cookie consent.
     */
    #[Route('/cookie-consent/view', name: 'cookie_consent.view')]
    public function view(): Response
    {
        $this->setLocale($this->getCurrentRequest());

        $consentFormDto = new ConsentFormDto(
            $this->createSimpleConsentForm()->createView(),
            $this->createDetailedConsentForm()->createView(),
            $this->position,
            $this->readMoreRoute,
            $this->theme
        );

        try {
            $response = new Response($this->twigEnvironment->render('@CookieConsent/cookie_consent.html.twig', $consentFormDto->toArray()));

            // Cache in ESI should not be shared
            $response->setPrivate();
            $response->setMaxAge(0);
            $response->headers->addCacheControlDirective('no-store');

            return $response;
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Set locale if available as GET parameter.
     */
    private function setLocale(Request $request): void
    {
        $locale = $request->query->get('locale', $request->getLocale());
        if (empty($locale) === false) {
            $this->translator->setLocale($locale);
            $request->setLocale($locale);
        }
    }
}
