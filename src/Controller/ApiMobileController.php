<?php

namespace App\Controller;

use App\Entity\ContactInquiry;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ShopInfoService;
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
        private ValidatorInterface $validator,
        private ShopInfoService $shopInfoService,
    ) {}

    private function noCacheJson(array $payload, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = new JsonResponse($payload, $status);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

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

    #[Route('/api/mobile/shop', name: 'api_mobile_shop', methods: ['GET'])]
    public function shop(): JsonResponse
    {
        $products = $this->productRepository->findBy([], ['id' => 'DESC']);
        $categories = $this->categoryRepository->findAll();

        return $this->noCacheJson([
            'status' => 'success',
            'data' => $this->shopInfoService->toArray(count($products), count($categories)),
            'syncedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/api/mobile/products', name: 'api_mobile_products', methods: ['GET'])]
    public function getProducts(Request $request): JsonResponse
    {
        $categoryId = $request->query->get('categoryId');
        $products = $categoryId
            ? $this->productRepository->findBy(['category' => (int) $categoryId], ['id' => 'DESC'])
            : $this->productRepository->findBy([], ['id' => 'DESC']);

        $data = array_map(fn (Product $p) => $this->serializeProduct($p), $products);

        return $this->noCacheJson([
            'status' => 'success',
            'data' => $data,
            'count' => count($data),
        ]);
    }

    #[Route('/api/mobile/products/{id}', name: 'api_mobile_product_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProduct(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->noCacheJson([
                'error' => 'Not found',
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->noCacheJson([
            'status' => 'success',
            'data' => $this->serializeProduct($product),
        ]);
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

        return $this->noCacheJson([
            'status' => 'success',
            'data' => $data,
            'count' => count($data),
        ]);
    }

    #[Route('/api/mobile/status', name: 'api_mobile_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $conn = $this->entityManager->getConnection();
        $params = $conn->getParams();
        $products = $this->productRepository->findBy([], ['id' => 'DESC']);

        return $this->noCacheJson([
            'status' => 'success',
            'database' => [
                'host' => $params['host'] ?? null,
                'port' => $params['port'] ?? null,
                'name' => $params['dbname'] ?? null,
            ],
            'productCount' => count($products),
            'productNames' => array_map(fn (Product $p) => $p->getName(), $products),
            'shop' => $this->shopInfoService->toArray(count($products)),
        ]);
    }

    #[Route('/api/mobile/contact', name: 'api_mobile_contact', methods: ['POST'])]
    public function submitContact(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['email']) || !isset($data['message'])) {
            return $this->noCacheJson([
                'error' => 'Validation failed',
                'message' => 'Name, email, and message are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) $data['name']);
        $email = trim((string) $data['email']);
        $message = trim((string) $data['message']);

        if ($name === '' || $email === '' || $message === '') {
            return $this->noCacheJson([
                'error' => 'Validation failed',
                'message' => 'All fields must be filled',
            ], Response::HTTP_BAD_REQUEST);
        }

        $inquiry = (new ContactInquiry())
            ->setName($name)
            ->setEmail($email)
            ->setMessage($message)
            ->setSource('mobile');

        $this->entityManager->persist($inquiry);
        $this->entityManager->flush();

        return $this->noCacheJson([
            'success' => true,
            'message' => 'Contact form submitted successfully. Our team will review it in the admin dashboard.',
            'id' => $inquiry->getId(),
        ]);
    }
}
