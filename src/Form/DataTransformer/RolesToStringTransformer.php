<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RolesToStringTransformer implements DataTransformerInterface
{
    /**
     * Transforms an array (roles) to a string (single role)
     */
    public function transform($roles): ?string
    {
        if (null === $roles || !is_array($roles)) {
            return null;
        }

        // Remove ROLE_USER as it's automatically added
        $roles = array_filter($roles, fn($r) => $r !== 'ROLE_USER');
        
        if (empty($roles)) {
            return 'ROLE_USER';
        }

        return array_values($roles)[0];
    }

    /**
     * Transforms a string (single role) to an array (roles)
     */
    public function reverseTransform($value): array
    {
        if (null === $value || $value === '' || $value === 'ROLE_USER') {
            return [];
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        return [$value];
    }
}

