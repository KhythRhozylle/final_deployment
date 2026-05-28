<?php

namespace App\Controller;

use App\Service\MobileOrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiMobileOrderController extends AbstractController
{
    public function __construct(
        private readonly MobileOrderService $mobileOrderService,
    ) {}

    #[Route('/api/mobile/orders', name: 'api_mobile_orders_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->jsonError('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        $customer = $data['customer'] ?? null;
        $items = $data['items'] ?? null;
        if (!\is_array($customer) || !\is_array($items) || $items === []) {
            return $this->jsonError('Customer details and cart items are required', Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->mobileOrderService->placeOrder($customer, $items);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Order placed successfully. Our team will confirm your order soon.',
            'data' => $result,
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/mobile/orders', name: 'api_mobile_orders_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $email = trim((string) $request->query->get('email', ''));
        if ($email === '') {
            return $this->jsonError('Email query parameter is required', Response::HTTP_BAD_REQUEST);
        }

        $orders = $this->mobileOrderService->listOrdersForEmail($email);

        $response = new JsonResponse([
            'status' => 'success',
            'data' => $orders,
            'count' => \count($orders),
        ]);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }

    #[Route('/api/mobile/orders/{orderGroupId}', name: 'api_mobile_orders_show', methods: ['GET'])]
    public function show(string $orderGroupId, Request $request): JsonResponse
    {
        $email = trim((string) $request->query->get('email', ''));
        if ($email === '') {
            return $this->jsonError('Email query parameter is required', Response::HTTP_BAD_REQUEST);
        }

        $order = $this->mobileOrderService->getOrderGroup($orderGroupId, $email);
        if ($order === null) {
            return $this->jsonError('Order not found', Response::HTTP_NOT_FOUND);
        }

        $response = new JsonResponse([
            'status' => 'success',
            'data' => $order,
        ]);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response;
    }

    public function submitPayment(string $orderGroupId, Request $request): JsonResponse
    {
        $email = trim((string) $request->request->get('email', ''));
        if ($email === '') {
            return $this->jsonError('Email is required', Response::HTTP_BAD_REQUEST);
        }

        $method = trim((string) $request->request->get('payment_method', ''));
        $reference = trim((string) $request->request->get('reference_number', ''));
        /** @var UploadedFile|null $proof */
        $proof = $request->files->get('payment_proof');

        try {
            $result = $this->mobileOrderService->submitGroupPayment(
                $orderGroupId,
                $email,
                $method,
                $reference !== '' ? $reference : null,
                $proof instanceof UploadedFile ? $proof : null,
            );
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            $status = str_contains(strtolower($message), 'not found')
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_BAD_REQUEST;

            return $this->jsonError($message, $status);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => $result['message'],
            'data' => [
                'orderGroupId' => $result['orderGroupId'],
                'paymentMethod' => $result['paymentMethod'],
                'paymentStatus' => $result['paymentStatus'],
                'paymentProofPath' => $result['paymentProofPath'],
                'referenceNumber' => $result['referenceNumber'],
            ],
        ], Response::HTTP_CREATED);
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return new JsonResponse([
            'error' => 'Request failed',
            'message' => $message,
        ], $status);
    }
}
