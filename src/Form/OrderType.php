<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEditMode = $options['edit_mode'] ?? false;
        
        $builder
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => 'name',
                'label' => 'Customer',
                'placeholder' => 'Select a customer',
                'required' => true,
                'disabled' => $isEditMode, // Disable in edit mode
            ])
            ->add('productName', TextType::class, [
                'label' => 'Product Name',
                'attr' => ['placeholder' => 'Enter product name'],
                'disabled' => $isEditMode, // Disable in edit mode
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantity',
                'scale' => 0,
                'attr' => [
                    'min' => 1,
                    'step' => 1,
                ],
                'disabled' => $isEditMode, // Disable in edit mode
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'scale' => 2,
                'attr' => ['placeholder' => 'Enter price'],
                'disabled' => $isEditMode, // Disable in edit mode
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Confirmed' => 'confirmed',
                    'Preparing' => 'preparing',
                    'Out for Delivery' => 'out_for_delivery',
                    'Delivered' => 'delivered',
                    'Processing (legacy)' => 'processing',
                    'Cancelled' => 'cancelled',
                ],
            ])
            ->add('orderDate', DateTimeType::class, [
                'label' => 'Order Date',
                'widget' => 'single_text',
                'disabled' => $isEditMode, // Disable in edit mode
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'csrf_protection' => true, // ✅ ensures CSRF token is generated
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'order_item', // unique ID for this form
            'edit_mode' => false, // Option to enable edit mode (only status editable)
        ]);
    }
}
