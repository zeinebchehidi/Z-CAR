<?php

namespace App\Form;

use App\Entity\Category;
use App\Enum\FuelType;
use App\Enum\Transmission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Filters of the /cars page. Sent with GET, so the URL can be shared/bookmarked.
 */
class CarSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'required' => false,
                'placeholder' => 'All categories',
            ])
            ->add('fuelType', EnumType::class, [
                'class' => FuelType::class,
                'label' => 'Fuel',
                'required' => false,
                'placeholder' => 'Any',
            ])
            ->add('transmission', EnumType::class, [
                'class' => Transmission::class,
                'label' => 'Gearbox',
                'required' => false,
                'placeholder' => 'Any',
            ])
            ->add('minPrice', NumberType::class, [
                'label' => 'Min € / day',
                'required' => false,
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('maxPrice', NumberType::class, [
                'label' => 'Max € / day',
                'required' => false,
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('start', DateType::class, [
                'label' => 'Pickup date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('end', DateType::class, [
                'label' => 'Return date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            // A search only reads data, so no CSRF token (it would also clutter the URL).
            'csrf_protection' => false,
            'constraints' => [
                new Assert\Callback(function (?array $data, ExecutionContextInterface $context): void {
                    if (($data['start'] ?? null) && ($data['end'] ?? null) && $data['end'] < $data['start']) {
                        $context->buildViolation('The return date must be on or after the pickup date.')
                            ->atPath('[end]')
                            ->addViolation();
                    }
                }),
            ],
        ]);
    }

    // No prefix: fields are sent as ?category=1&start=... (same names as the home page search bar).
    public function getBlockPrefix(): string
    {
        return '';
    }
}
