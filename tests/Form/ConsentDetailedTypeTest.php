<?php

namespace kpeu3u\CookieConsentBundle\tests\Form;

use kpeu3u\CookieConsentBundle\Enum\FormSubmitName;
use kpeu3u\CookieConsentBundle\Form\ConsentCategoryType;
use kpeu3u\CookieConsentBundle\Form\ConsentCategoryTypeModel;
use kpeu3u\CookieConsentBundle\Form\ConsentDetailedType;
use kpeu3u\CookieConsentBundle\Form\ConsentDetailedTypeModel;
use kpeu3u\CookieConsentBundle\Form\ConsentSimpleType;
use kpeu3u\CookieConsentBundle\Form\ConsentVendorType;
use kpeu3u\CookieConsentBundle\Form\ConsentVendorTypeModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConsentDetailedTypeTest extends TypeTestCase
{
    #[Test]
    #[DataProvider('submittedFormProvider')]
    public function shouldHaveClickedButton($formData, $expectedClickedButton): void
    {
        $formModel = new ConsentDetailedTypeModel();

        foreach ($formData['categories'] as $category) {

            $consentCategory = new ConsentCategoryTypeModel();
            $consentCategory->setName($category['name']);

            foreach ($category['vendors'] as $vendor) {
                $consentCookie = new ConsentVendorTypeModel();

                // explicitly set fields from formData
                $consentCookie->setName($vendor['name']);
                $consentCookie->setConsentGiven($vendor['consentGiven']);
                $consentCookie->setDescriptionKey($vendor['descriptionKey']);

                $consentCategory->getVendors()->add($consentCookie);
            }

            $formModel->getCategories()->add($consentCategory);
        }

        $form = $this->factory->create(ConsentDetailedType::class, $formModel);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($form->getClickedButton()->getName(), $expectedClickedButton);
    }


    #[Test]
    public function shouldReturnFormWithSubmitButtons(): void
    {
        /** @var ConsentSimpleType $form */
        $form = $this->factory->create(ConsentDetailedType::class);

        $this->assertInstanceOf(FormInterface::class, $form);
        $this->assertArrayHasKey('accept_all', $form->all());
        $this->assertArrayHasKey('reject_all', $form->all());
        $this->assertArrayHasKey('categories', $form->all());
    }

    /**
     * @throws Exception
     */
    protected function getExtensions(): array
    {
        $translatorInterfaceMock = $this->createMock(TranslatorInterface::class);

        $consentDetailedType = new ConsentDetailedType($translatorInterfaceMock);
        $consentCategoryType = new ConsentCategoryType($translatorInterfaceMock);
        $consentCookieType = new ConsentVendorType($translatorInterfaceMock);

        return [
            new PreloadedExtension([$consentDetailedType, $consentCategoryType, $consentCookieType], []),
        ];
    }

    public static function submittedFormProvider(): array
    {
        return [
            'dataset:save_consent_settings_clicked' => [
                [
                    FormSubmitName::SAVE_CONSENT_SETTINGS => true,
                    'categories' => [
                        [
                            'name' => 'analytics',
                            'vendors' => [
                                [
                                    'name' => 'googleanalytics',
                                    'consentGiven' => false,
                                    'descriptionKey' => 'googleanalytics',
                                ]
                            ],
                        ],
                        [
                            'name' => 'tracking',
                            'vendors' => [
                                [
                                    'name' => 'googletagmanager',
                                    'consentGiven' => true,
                                    'descriptionKey' => 'googletagmanager',
                                ]
                            ],
                        ],
                        [
                            'name' => 'social_media',
                            'vendors' => [
                                [
                                    'name' => 'meta',
                                    'consentGiven' => false,
                                    'descriptionKey' => 'meta',
                                ],
                                [
                                    'name' => 'google',
                                    'consentGiven' => true,
                                    'descriptionKey' => 'google',
                                ]
                            ],
                        ],
                    ],
                    'consent_version' => 1,
                ],
                FormSubmitName::SAVE_CONSENT_SETTINGS,
            ],
            'dataset:accept_all_clicked' => [
                [
                    FormSubmitName::ACCEPT_ALL => true,
                ],
                FormSubmitName::ACCEPT_ALL,
            ],
            'dataset:reject_all_clicked' => [
                [
                    FormSubmitName::REJECT_ALL => true,
                ],
                FormSubmitName::REJECT_ALL,
            ],
        ];
    }
}
