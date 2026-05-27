<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

/**
 * Customer-facing portal for registered ROLE_USER accounts.
 * Provides product browsing, cart, checkout, and order history.
 */
#[Route('/portal')]
#[IsGranted('ROLE_USER')]
final class CustomerPortalController extends AbstractController
{
    // ------------------------------------------------------------------ shop

    #[Route('', name: 'app_customer_portal', methods: ['GET'])]
    public function home(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        Request $request,
    ): Response {
        $search = trim((string) $request->query->get('search', ''));
        $categoryId = $request->query->get('category', '');
        $categoryId = ($categoryId !== '' && is_numeric($categoryId)) ? (int) $categoryId : null;

        $products = $productRepository->searchAndFilter(
            $search !== '' ? $search : null,
            $categoryId
        );

        return $this->render('customer_portal/home.html.twig', [
            'products'    => $products,
            'categories'  => $categoryRepository->findAll(),
            'search'      => $search,
            'category_id' => $categoryId,
        ]);
    }

    // ------------------------------------------------------------------ checkout

    /**
     * Place an order for the current logged-in user.
     * Expects JSON: { "items": [{"productId": 1, "quantity": 2}], "notes": "..." }
     */
    #[Route('/checkout', name: 'app_portal_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        ProductRepository $productRepository,
        CustomerRepository $customerRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data) || empty($data['items'])) {
            return $this->json(['error' => 'No items in request'], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Find or create Customer record linked to this User account.
        $customer = $customerRepository->findOneBy(['email' => $user->getEmail()]);
        if (!$customer) {
            $customer = new Customer();
            $customer->setEmail($user->getEmail());
            $customer->setName($user->getName());
            $customer->setCustomerName($user->getName());
            $username = preg_replace('/[^a-z0-9]/', '', strtolower(explode('@', $user->getEmail())[0])) ?: 'user';
            // make username unique
            $base = $username;
            $i = 0;
            while ($customerRepository->findOneBy(['username' => $username])) {
                $username = $base . (++$i);
            }
            $customer->setUsername($username);
            $em->persist($customer);
        }

        $orderGroupId = Uuid::v4()->toRfc4122();
        $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;
        $total = 0.0;
        $orderCount = 0;

        foreach ($data['items'] as $line) {
            $productId = isset($line['productId']) ? (int) $line['productId'] : null;
            $qty = isset($line['quantity']) ? (float) $line['quantity'] : 0;

            if (!$productId || $qty <= 0) {
                continue;
            }

            $product = $productRepository->find($productId);
            if (!$product) {
                return $this->json(['error' => "Product #{$productId} not found"], Response::HTTP_BAD_REQUEST);
            }
            if ($product->getStock() < $qty) {
                return $this->json([
                    'error' => sprintf('Not enough stock for "%s". Available: %d.', $product->getName(), $product->getStock()),
                ], Response::HTTP_BAD_REQUEST);
            }

            $order = new Order();
            $order->setCustomer($customer);
            $order->setCreatedBy($user);
            $order->setProductName($product->getName());
            $order->setProductId($product->getId());
            $order->setQuantity($qty);
            $order->setPrice((float) $product->getPrice());
            $order->setStatus('pending');
            $order->setOrderGroupId($orderGroupId);
            $order->setSource('web');
            $order->setNotes($notes);
            $order->setOrderDate(new \DateTime());
            $order->setStockDeducted(false);

            $em->persist($order);

            $total += (float) $product->getPrice() * $qty;
            ++$orderCount;
        }

        if ($orderCount === 0) {
            return $this->json(['error' => 'No valid items to order'], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json([
            'success'      => true,
            'orderGroupId' => $orderGroupId,
            'total'        => round($total, 2),
            'itemCount'    => $orderCount,
            'message'      => 'Order placed! Our team will confirm it soon.',
        ], Response::HTTP_CREATED);
    }

    // ------------------------------------------------------------------ order history

    #[Route('/orders', name: 'app_portal_orders', methods: ['GET'])]
    public function orders(OrderRepository $orderRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $orders = $orderRepository->findBy(
            ['createdBy' => $user],
            ['orderDate' => 'DESC']
        );

        return $this->render('customer_portal/orders.html.twig', [
            'orders' => $orders,
        ]);
    }
}
