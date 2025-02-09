<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Model\Order;

use BitBag\OpenMarketplace\Entity\OrderInterface;
use BitBag\OpenMarketplace\Entity\OrderItem;
use BitBag\OpenMarketplace\Entity\OrderItemInterface;
use BitBag\OpenMarketplace\Entity\ProductInterface;
use BitBag\OpenMarketplace\Entity\ShipmentInterface;
use BitBag\OpenMarketplace\Entity\VendorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductVariantInterface;

trait OrderTrait
{
    /** @var Collection<int, OrderItemInterface> */
    protected $items;

    protected ?VendorInterface $vendor = null;

    protected ?OrderInterface $primaryOrder;

    /** @var Collection<int, OrderInterface> */
    protected Collection $secondaryOrders;

    public function __construct()
    {
        parent::__construct();
        $this->secondaryOrders = new ArrayCollection();
    }

    public function getVendor(): ?VendorInterface
    {
        return $this->vendor;
    }

    public function setVendor(?VendorInterface $vendor): void
    {
        $this->vendor = $vendor;
    }

    public function getPrimaryOrder(): ?OrderInterface
    {
        return $this->primaryOrder;
    }

    public function setPrimaryOrder(?OrderInterface $primaryOrder): void
    {
        $this->primaryOrder = $primaryOrder;
    }

    public function addSecondaryOrder(OrderInterface $secondaryOrder): void
    {
        $this->secondaryOrders->add($secondaryOrder);
    }

    /** @return Collection<int, OrderInterface> */
    public function getSecondaryOrders(): Collection
    {
        return $this->secondaryOrders;
    }

    public function hasVendorShipment(?VendorInterface $vendor): bool
    {
        /** @var ShipmentInterface $shipment */
        foreach ($this->getShipments() as $shipment) {
            if ($shipment->getVendor() === $vendor) {
                return true;
            }
        }

        return false;
    }

    public function getVendorsFromOrderItems(): array
    {
        $vendors = [];

        foreach ($this->getItems() as $item) {
            /** @var ProductInterface $product */
            /** @phpstan-ignore-next-line */
            $product = $item->getVariant()?->getProduct();
            $vendor = $product->getVendor();

            if (false === in_array($vendor, $vendors)) {
                $vendors[] = $vendor;
            }
        }

        return $vendors;
    }

    public function getShipmentByVendor(?VendorInterface $vendor): ?ShipmentInterface
    {
        /** @var ShipmentInterface $shipment */
        foreach ($this->getShipments() as $shipment) {
            if ($shipment->getVendor() === $vendor) {
                return $shipment;
            }
        }

        return null;
    }

    public function getShipmentWithoutVendor(): ?ShipmentInterface
    {
        return $this->getShipmentByVendor(null);
    }

    public function hasShippableItemsWithVendor(?VendorInterface $vendor): bool
    {
        /** @var OrderItem $item */
        foreach ($this->getItems() as $item) {
            /** @var ProductInterface $product */
            $product = $item->getProduct();

            /** @var ProductVariantInterface $variant */
            $variant = $item->getVariant();

            if ($vendor === $product->getVendor() && true === $variant->isShippingRequired()) {
                return true;
            }
        }

        return false;
    }

    public function getSecondaryPayments(): Collection
    {
        $payments = new ArrayCollection();
        $secondaryOrders = $this->getSecondaryOrders();
        if (0 === $secondaryOrders->count()) {
            return $payments;
        }

        foreach ($secondaryOrders as $order) {
            $payments->add($order->getPayments()->first());
        }

        return $payments;
    }
}
