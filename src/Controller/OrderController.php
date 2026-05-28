<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Service\ActivityLogService;
use App\Service\OrderApprovalService;
use App\Service\OrderLiveRevisionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
#[IsGranted('ROLE_STAFF')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(
        OrderRepository $orderRepository,
        OrderApprovalService $orderApprovalService,
        OrderLiveRevisionService $liveRevisionService,
    ): Response {
        $view = $this->buildOrdersViewData($orderRepository, $orderApprovalService);

        return $this->render('order/index.html.twig', [
            ...$view,
            'liveRevision' => $liveRevisionService->current(),
        ]);
    }

    /**
     * Polled by admin orders page — returns fresh HTML when a customer transacts on mobile.
     */
    #[Route('/live-updates', name: 'app_order_live_updates', methods: ['GET'])]
    public function liveUpdates(
        OrderRepository $orderRepository,
        OrderApprovalService $orderApprovalService,
        OrderLiveRevisionService $liveRevisionService,
    ): JsonResponse {
        $view = $this->buildOrdersViewData($orderRepository, $orderApprovalService);
        $revision = $liveRevisionService->current();

        return new JsonResponse([
            'revision' => $revision,
            'statsHtml' => $this->renderView('order/_stats.html.twig', $view),
            'pendingHtml' => $this->renderView('order/_pending_mobile.html.twig', $view),
            'tableRowsHtml' => $this->renderView('order/_table_rows.html.twig', $view),
            'orderCount' => \count($view['orders']),
            'pendingMobileCount' => \count($view['pendingMobileGroups']),
        ]);
    }

    /**
     * @return array{orders: list<Order>, pendingMobileGroups: list<array<string, mixed>>}
     */
    private function buildOrdersViewData(
        OrderRepository $orderRepository,
        OrderApprovalService $orderApprovalService,
    ): array {
        $orders = $this->isGranted('ROLE_ADMIN')
            ? $orderRepository->findForAdminListing()
            : $orderRepository->findForAdminListing($this->getUser());

        $pendingMobileGroups = [];
        if ($this->isGranted('ROLE_ADMIN')) {
            foreach ($orderRepository->findPendingMobileOrderGroupIds() as $groupId) {
                try {
                    $pendingMobileGroups[] = $orderApprovalService->buildGroupSummary($groupId);
                } catch (\InvalidArgumentException) {
                    // skip invalid group
                }
            }
        }

        return [
            'orders' => $orders,
            'pendingMobileGroups' => $pendingMobileGroups,
        ];
    }

    #[Route('/mobile/{orderGroupId}/review', name: 'app_order_group_review', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reviewMobileGroup(
        string $orderGroupId,
        OrderApprovalService $orderApprovalService,
    ): Response {
        try {
            $summary = $orderApprovalService->buildGroupSummary($orderGroupId);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('order/group_review.html.twig', [
            'summary' => $summary,
        ]);
    }

    #[Route('/mobile/{orderGroupId}/approve', name: 'app_order_group_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approveMobileGroup(
        string $orderGroupId,
        Request $request,
        OrderApprovalService $orderApprovalService,
        ActivityLogService $logService,
    ): Response {
        if (!$this->isCsrfTokenValid('approve_order_group', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('app_order_group_review', ['orderGroupId' => $orderGroupId]);
        }

        try {
            $count = $orderApprovalService->approveGroup($orderGroupId, $this->getUser());
            $logService->logUpdate($this->getUser(), 'OrderGroup', 0, [
                'orderGroupId' => $orderGroupId,
                'action' => 'approved',
                'lines' => (string) $count,
            ]);
            $this->addFlash('success', sprintf('Order approved and confirmed (%d item(s)). Stock has been reserved.', $count));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/mobile/{orderGroupId}/reject', name: 'app_order_group_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function rejectMobileGroup(
        string $orderGroupId,
        Request $request,
        OrderApprovalService $orderApprovalService,
        ActivityLogService $logService,
    ): Response {
        if (!$this->isCsrfTokenValid('reject_order_group', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('app_order_group_review', ['orderGroupId' => $orderGroupId]);
        }

        try {
            $count = $orderApprovalService->rejectGroup($orderGroupId, $this->getUser());
            $logService->logUpdate($this->getUser(), 'OrderGroup', 0, [
                'orderGroupId' => $orderGroupId,
                'action' => 'rejected',
                'lines' => (string) $count,
            ]);
            $this->addFlash('success', sprintf('Order rejected (%d item(s)). Customer will see status as cancelled.', $count));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/{id}/accept', name: 'app_order_accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function acceptOrder(
        Order $order,
        Request $request,
        OrderApprovalService $orderApprovalService,
        ActivityLogService $logService,
    ): Response {
        if (!$this->isCsrfTokenValid('approve_order', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_order_index');
        }

        try {
            $count = $orderApprovalService->acceptOrder($order, $this->getUser());
            $logService->logUpdate($this->getUser(), 'Order', $order->getId(), [
                'action' => 'accepted',
                'lines' => (string) $count,
                'orderGroupId' => $order->getOrderGroupId() ?? '',
            ]);
            $this->addFlash('success', sprintf('Order accepted (%d item(s)). Customer will see "Order has been placed".', $count));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/{id}/cancel', name: 'app_order_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function cancelOrder(
        Order $order,
        Request $request,
        OrderApprovalService $orderApprovalService,
        ActivityLogService $logService,
    ): Response {
        if (!$this->isCsrfTokenValid('cancel_order', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_order_index');
        }

        try {
            $count = $orderApprovalService->cancelOrder($order);
            $logService->logUpdate($this->getUser(), 'Order', $order->getId(), [
                'action' => 'cancelled',
                'lines' => (string) $count,
                'orderGroupId' => $order->getOrderGroupId() ?? '',
            ]);
            $this->addFlash('success', sprintf('Order cancelled (%d item(s)). Customer will see "Order cancelled".', $count));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ActivityLogService $logService,
        ProductRepository $productRepository,
        OrderLiveRevisionService $orderLiveRevision,
    ): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productName = (string) $order->getProductName();
            $quantity = (int) $order->getQuantity();

            if ($productName === '') {
                $this->addFlash('error', 'Product name is required.');
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            if ($quantity <= 0) {
                $this->addFlash('error', 'Quantity must be greater than 0.');
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            // Deduct stock from the matching product by name.
            $product = $productRepository->findOneBy(['name' => $productName]);

            if (!$product) {
                $this->addFlash('error', 'No product found for that product name. Stock could not be deducted.');

                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            $availableStock = $product->getStock();
            if ($availableStock < $quantity) {
                $this->addFlash('error', sprintf('Not enough stock. Available: %d, requested: %d.', $availableStock, $quantity));

                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            $product->setStock($availableStock - $quantity);
            $entityManager->persist($product);

            // Set createdBy for ownership tracking
            $order->setCreatedBy($this->getUser());
            
            // Set orderDate if not already set
            if (!$order->getOrderDate()) {
                $order->setOrderDate(new \DateTime());
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $orderLiveRevision->bump();

            $logService->logCreate($this->getUser(), 'Order', $order->getId(), [
                'productName' => $order->getProductName(),
                'quantity' => (string) $order->getQuantity()
            ]);

            // Track stock changes for auditing.
            $logService->logUpdate($this->getUser(), 'Product', $product->getId(), [
                'name' => $product->getName(),
                'stock_change' => '-' . (string) $quantity,
                'stock_after' => (string) $product->getStock(),
            ]);

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        // Staff can only view their own records
        if (!$this->isGranted('ROLE_ADMIN') && $order->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only view your own records.');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Order $order,
        EntityManagerInterface $entityManager,
        ActivityLogService $logService,
        OrderApprovalService $orderApprovalService,
        OrderLiveRevisionService $orderLiveRevision,
    ): Response {
        // Staff can only edit their own records
        if (!$this->isGranted('ROLE_ADMIN') && $order->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own records.');
        }

        // Create form with edit_mode enabled (only status will be editable)
        $form = $this->createForm(OrderType::class, $order, ['edit_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $status = $form->get('status')->getData();

            if ($status !== null) {
                $normalized = \App\Service\MobileOrderService::normalizeStatus((string) $status);
                if ($order->getSource() === 'mobile' && $order->getOrderGroupId()) {
                    $orderApprovalService->syncMobileGroupStatus($order->getOrderGroupId(), $normalized);
                } else {
                    $order->setStatus($normalized);
                    $entityManager->flush();
                    $orderLiveRevision->bump();
                }
            } else {
                $entityManager->flush();
                $orderLiveRevision->bump();
            }

            $logService->logUpdate($this->getUser(), 'Order', $order->getId(), [
                'productName' => $order->getProductName(),
                'status' => $order->getStatus()
            ]);

            $this->addFlash('success', 'Order status updated successfully!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Order $order,
        EntityManagerInterface $entityManager,
        ActivityLogService $logService,
        OrderLiveRevisionService $orderLiveRevision,
    ): Response
    {
        // Staff can only delete their own records
        if (!$this->isGranted('ROLE_ADMIN') && $order->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own records.');
        }

        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $orderId = $order->getId();
            $orderData = [
                'productName' => $order->getProductName(),
                'quantity' => (string) $order->getQuantity()
            ];
            $entityManager->remove($order);
            $entityManager->flush();
            $orderLiveRevision->bump();
            $logService->logDelete($this->getUser(), 'Order', $orderId, $orderData);
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
