<?php

namespace App\Controller;

use App\Repository\ContactInquiryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contact/inquiries')]
#[IsGranted('ROLE_STAFF')]
final class ContactInquiryController extends AbstractController
{
    #[Route('/', name: 'app_contact_inquiries', methods: ['GET'])]
    public function index(ContactInquiryRepository $repository): Response
    {
        return $this->render('contact/inquiries.html.twig', [
            'inquiries' => $repository->findRecent(100),
        ]);
    }
}
