<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiMobileController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {}

    private function serializeProduct(Product $product): array
    {
        $category = $product->getCategory();

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
            'image' => $product->getImage(),
            'stock' => $product->getStock(),
            'inStock' => $product->getStock() > 0,
            'category' => $category ? $category->getName() : null,
            'categoryId' => $category ? $category->getId() : null,
        ];
    }

    #[Route('/api/mobile/products', name: 'api_mobile_products', methods: ['GET'])]
    public function getProducts(Request $request): JsonResponse
    {
        $categoryId = $request->query->get('categoryId');
        $products = $categoryId
            ? $this->productRepository->findBy(['category' => (int) $categoryId])
            : $this->productRepository->findAll();

        $data = array_map(fn (Product $p) => $this->serializeProduct($p), $products);

        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
            'count' => count($data),
        ], Response::HTTP_OK);
    }

    #[Route('/api/mobile/products/{id}', name: 'api_mobile_product_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProduct(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return new JsonResponse([
                'error' => 'Not found',
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => $this->serializeProduct($product),
        ], Response::HTTP_OK);
    }

    #[Route('/api/mobile/categories', name: 'api_mobile_categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        $categories = $this->categoryRepository->findAll();

        $data = array_map(function ($category) {
            return [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'product_count' => $category->getProducts()->count(),
            ];
        }, $categories);

        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
            'count' => count($data),
        ], Response::HTTP_OK);
    }

    #[Route('/api/mobile/contact', name: 'api_mobile_contact', methods: ['POST'])]
    public function submitContact(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['email']) || !isset($data['message'])) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'message' => 'Name, email, and message are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (empty(trim($data['name'])) || empty(trim($data['email'])) || empty(trim($data['message']))) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'message' => 'All fields must be filled',
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Contact form submitted successfully',
        ], Response::HTTP_OK);
    }
}
