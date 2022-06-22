<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMultiVendorMarketplacePlugin\Controller;

use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\Vendor;
use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\VendorInterface;
use BitBag\SyliusMultiVendorMarketplacePlugin\Exception\UserNotFoundException;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

final class VendorController extends ResourceController
{
    public function createAction(Request $request): Response
    {
        try {
            return parent::createAction($request);
        } catch (UserNotFoundException $exception) {
            return $this->redirectToRoute('sylius_shop_login');
        } catch (TokenNotFoundException $exception) {
            return $this->redirectToRoute('sylius_shop_login');
        }
    }

    public function verifyVendorAction(Request $request): Response
    {
        $vendorId = $request->attributes->get('id');

        $currentVendor = $this->manager->getRepository(Vendor::class)->findOneBy(['id' => $vendorId]);
        $currentVendor->setStatus(VendorInterface::STATUS_VERIFIED);

        $this->manager->flush();

        $this->addFlash('success', 'bitbag_mvm_plugin.ui.vendor_verified');

        return $this->redirectToRoute('bitbag_mvm_plugin_admin_vendor_index');
    }

    public function enablingVendorAction(Request $request): Response
    {
        $currentVendor = $this->manager->getRepository(Vendor::class)->findOneBy(['id' => $request->attributes->get('id')]);
        $currentVendor->setEnabled(!$currentVendor->isEnabled());
        $this->manager->flush();

        $messageSuffix = $currentVendor->isEnabled() ? 'enabled' : 'disabled';
        $this->addFlash('success', 'bitbag_mvm_plugin.ui.vendor_' . $messageSuffix);

        return $this->redirectToRoute('bitbag_mvm_plugin_admin_vendor_index');
    }
}
