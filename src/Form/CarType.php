<?php

namespace App\Form;

use App\Entity\Car;
use App\Entity\Category;
use App\Enum\FuelType;
use App\Enum\Transmission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

/**
 * Admin form to create / edit a car.
 */
class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand')
            ->add('model')
            ->add('year', IntegerType::class)
            ->add('plateNumber', null, [
                'label' => 'Plate number',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a category',
            ])
            ->add('pricePerDay', MoneyType::class, [
                'label' => 'Price per day',
                'currency' => 'EUR',
                'input' => 'string', // the entity stores decimals as strings
            ])
            ->add('fuelType', EnumType::class, [
                'class' => FuelType::class,
                'label' => 'Fuel',
                'choice_label' => fn (FuelType $fuel) => ucfirst($fuel->value),
            ])
            ->add('transmission', EnumType::class, [
                'class' => Transmission::class,
                'choice_label' => fn (Transmission $transmission) => ucfirst($transmission->value),
            ])
            ->add('seats', IntegerType::class)
            // Not mapped: the entity only stores the filename, the controller saves the file.
            ->add('imageFile', FileType::class, [
                'label' => 'Photo (JPG, PNG or WebP, max 2 MB)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Please upload a JPG, PNG or WebP image.',
                    ),
                ],
            ])
            ->add('isAvailable', CheckboxType::class, [
                'label' => 'Available for booking',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}
