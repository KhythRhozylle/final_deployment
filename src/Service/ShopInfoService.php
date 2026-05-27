<?php

namespace App\Service;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;

/**
 * Single source of shop metadata for mobile API and web.
 */
final class ShopInfoService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private string $shopName,
        private string $shopHours,
        private array $shopPhones,
        private string $shopEmail,
        private string $shopAddress,
        private array $shopServices,
    ) {}

    public function toArray(int $productCount = null, int $categoryCount = null): array
    {
        if ($productCount === null) {
            $productCount = count($this->productRepository->findAll());
        }
        if ($categoryCount === null) {
            $categoryCount = count($this->categoryRepository->findAll());
        }

        return [
            'name' => $this->shopName,
            'hours' => $this->shopHours,
            'phones' => $this->shopPhones,
            'email' => $this->shopEmail,
            'address' => $this->shopAddress,
            'services' => $this->shopServices,
            'productCount' => $productCount,
            'categoryCount' => $categoryCount,
        ];
    }
}
