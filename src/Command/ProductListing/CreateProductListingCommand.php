<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMultiVendorMarketplacePlugin\Command\ProductListing;

use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\CustomerInterface;
use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\ProductListing\ProductDraftInterface;
use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\ProductListing\ProductListingInterface;
use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\ProductListing\ProductListingPriceInterface;
use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\ProductListing\ProductTranslationInterface;
use BitBag\SyliusMultiVendorMarketplacePlugin\Repository\ProductListing\ProductDraftRepositoryInterface;
use BitBag\SyliusMultiVendorMarketplacePlugin\Repository\ProductListing\ProductListingRepositoryInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CreateProductListingCommand implements CreateProductListingCommandInterface
{
    private ProductListingRepositoryInterface $productListingRepository;

    private FactoryInterface $productListingFactoryInterface;

    private TokenStorageInterface $tokenStorage;

    private FactoryInterface $translationFactory;

    private FactoryInterface $draftFactory;

    private FactoryInterface $priceFactory;

    private ProductDraftRepositoryInterface $draftRepository;

    public function __construct(
        ProductListingRepositoryInterface $productListingRepository,
        FactoryInterface $productListingFactoryInterface,
        TokenStorageInterface $tokenStorage,
        FactoryInterface $translationFactory,
        FactoryInterface $draftFactory,
        FactoryInterface $priceFactory,
        ProductDraftRepositoryInterface $draftRepository
    ) {
        $this->productListingRepository = $productListingRepository;
        $this->productListingFactoryInterface = $productListingFactoryInterface;
        $this->tokenStorage = $tokenStorage;
        $this->translationFactory = $translationFactory;
        $this->draftFactory = $draftFactory;
        $this->priceFactory = $priceFactory;
        $this->draftRepository = $draftRepository;
    }

    public function create(ProductDraftInterface $productDraft, bool $isSend): void
    {
        /** @var ProductListingInterface $productListing */
        $productListing = $this->productListingFactoryInterface->createNew();
        /** @var TokenInterface $token */
        $token = $this->tokenStorage->getToken();
        /** @var ShopUserInterface $user */
        $user = $token->getUser();
        /** @var CustomerInterface $customer */
        $customer = $user->getCustomer();
        $vendor = $customer->getVendor();

        if (!$vendor) {
            throw new \Exception('Vendor not found.');
        }

        $productDraft = $this->formatTranslation($productDraft);

        if ($isSend) {
            $productDraft->setStatus(ProductDraftInterface::STATUS_UNDER_VERIFICATION);
            $productDraft->setPublishedAt(new \DateTime());
        }

        $productListing->setCode($productDraft->getCode());
        $productListing->addProductDrafts($productDraft);
        $productListing->setVendor($vendor);

        $productDraft->setProductListing($productListing);
        $this->productListingRepository->save($productListing);
    }

    private function formatTranslation(ProductDraftInterface $productDraft): ProductDraftInterface
    {
        /** @var ProductTranslationInterface $translation */
        foreach ($productDraft->getTranslations() as $translation) {
            $translation->setProductDraft($productDraft);
        }

        return $productDraft;
    }

    public function cloneProduct(ProductDraftInterface $productDraft, bool $isSend): ProductDraftInterface
    {
        $productListing = $productDraft->getProductListing();

        /** @var ProductDraftInterface $newProductDraft */
        $newProductDraft = $this->draftFactory->createNew();

        $newProductDraft->setVersionNumber($productDraft->getVersionNumber());
        $newProductDraft->newVersion();
        $newProductDraft->setCode($productDraft->getCode());
        $newProductDraft->setProductListing($productListing);

        if ($isSend) {
            $newProductDraft->setStatus(ProductDraftInterface::STATUS_UNDER_VERIFICATION);
        }

        $this->cloneTranslation($newProductDraft, $productDraft);

        $this->clonePrice($newProductDraft, $productDraft);

        $newProductDraft->setProductListing($productDraft->getProductListing());

        return $newProductDraft;
    }

    private function cloneTranslation(ProductDraftInterface $newProductDraft, ProductDraftInterface $productDraft): void
    {
        /** @var ProductTranslationInterface $translation */
        foreach ($productDraft->getTranslations() as $translation) {
            $locale = $translation->getLocale();
            if (null === $locale) {
                throw new \Exception('Cannot find translation locale.');
            }

            /** @var ProductTranslationInterface $newTranslation */
            $newTranslation = $this->translationFactory->createNew();
            $newTranslation->setName($translation->getName());
            $newTranslation->setProductDraft($newProductDraft);
            $newTranslation->setDescription($translation->getDescription());
            $newTranslation->setLocale($translation->getLocale());
            $newTranslation->setMetaDescription($translation->getMetaDescription());
            $newTranslation->setMetaKeywords($translation->getMetaKeywords());
            $newTranslation->setSlug($translation->getSlug());
            $newTranslation->setShortDescription($translation->getShortDescription());
            $newProductDraft->addTranslationsWithKey($newTranslation, $locale);
        }
    }

    private function clonePrice(ProductDraftInterface $newProductDraft, ProductDraftInterface $productDraft): void
    {
        /** @var ProductListingPriceInterface $price */
        foreach ($productDraft->getProductListingPrice() as $price) {
            /** @var ProductListingPriceInterface $newPrice */
            $newPrice = $this->priceFactory->createNew();
            $newPrice->setChannelCode($price->getChannelCode());
            $newPrice->setPrice($price->getPrice());
            $newPrice->setMinimumPrice($price->getMinimumPrice());
            $newPrice->setOriginalPrice($price->getOriginalPrice());
            $newPrice->setProductDraft($newProductDraft);
            $newProductDraft->addProductListingPriceWithKey($newPrice, $newPrice->getChannelCode());
        }
    }

    public function saveEdit(ProductDraftInterface $productDraft, bool $isSend): void
    {
        $this->formatTranslation($productDraft);

        if ($isSend) {
            $productDraft->setStatus(ProductDraftInterface::STATUS_UNDER_VERIFICATION);
            $productDraft->setPublishedAt(new \DateTime());
        }

        $this->draftRepository->save($productDraft);
    }
}
