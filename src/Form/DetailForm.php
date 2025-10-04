<?php
// namespace App\Form;

// use App\Entity\Detail;
// use App\Entity\Product;
// use Symfony\Bridge\Doctrine\Form\Type\EntityType;
// use Symfony\Component\Form\AbstractType;
// use Symfony\Component\Form\Extension\Core\Type\IntegerType;
// use Symfony\Component\Form\Extension\Core\Type\TextType;
// use Symfony\Component\Form\FormBuilderInterface;
// use Symfony\Component\OptionsResolver\OptionsResolver;
// use Symfony\Component\Validator\Constraints as Assert;

// class DetailForm extends AbstractType
// {
//     public function buildForm(FormBuilderInterface $builder, array $options)
//     {
//         $builder
//             ->add('product', EntityType::class, [
//                 'class' => Product::class,
//                 'choice_label' => 'name',
//                 'placeholder' => 'Seleccionar producto',
//                 'label' => 'Producto',
//                 'constraints' => [new Assert\NotBlank()]
//             ])
//             ->add('quantity', IntegerType::class, [
//                 'label' => 'Cantidad',
//                 'constraints' => [new Assert\NotBlank(), new Assert\Positive()]
//             ])
//             ->add('series', TextType::class, [
//                 'label' => 'Series (separadas por comas)',
//                 'constraints' => [
//                     new Assert\NotBlank(),
//                     new Assert\Regex([
//                         'pattern' => '/^([^,]+,)*[^,]+$/',
//                         'message' => 'Debe ser una lista separada por comas.'
//                     ])
//                 ]
//             ]);
//     }

//     public function configureOptions(OptionsResolver $resolver)
//     {
//         $resolver->setDefaults([
//             'data_class' => Detail::class,
//         ]);
//     }
// }
namespace App\Form;

use App\Entity\Detail;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DetailForm extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('product', EntityType::class, [
        'class' => Product::class,
        'attr' => ['class' => 'select2Prod'],
        'choices' => $options['available_products'],
        'choice_label' => function (Product $product) {
          return $product->getType() . ' ' . $product->getBrand() . ', ' . $product->getModel() . ' (Máx. ' . $product->getQuantity() . ')';
        }, // Ajusta según la propiedad del producto
        'label' => false,
        'block_name' => 'detail_product'
      ])
      ->add('quantity', IntegerType::class, [
        'label' => false,
        'block_name' => 'detail_quantity',
        'attr' => [
          'min' => 1,
          //'value' => 1 Por defecto debe enviarse como mínimo 1, pero como se debe validar y se puede re-renderizar con los valores previos, no puedo pisarle ese valor
          // Es mejor que no tenga valor inicialmente y sí tenga el valor adecuado en caso de no ser válido.
        ],
        'constraints' => [
          new Assert\Type(['type' => 'numeric', 'message' => 'La cantidad debe ser un número.']),
          new Assert\Positive(['message' => 'La cantidad debe ser mayor a 0.']),
          new Assert\Range([
            'min' => 1,
          ]),
        ],
      ])
      ->add('series', TextType::class, [
        'label' => false,
        'required' => true,
        'block_name' => 'detail_series',
        'constraints' => [
          new Assert\NotBlank(['message' => 'El campo "serie" no puede ser vacío.']),
          new Assert\Regex([
            'pattern' => '/^([^,]+,)*[^,]+$/',
            //'pattern' => '/^([^,]+(,[^,]+)*)?$/',
            //'pattern' => '/^([^,]+,)*[^,]+$/',
            'message' => 'La cadena debe contener elementos válidos separados por comas.',
          ]),
        ]
        ]);
      //->add('save', SubmitType::class, ['label' => 'Enviar y generar constancia']);


    // Evento para validar que la cantidad de series coincida con la cantidad indicada.
    $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
      // EVALUA PARIDAD ENTRE CANTIDAD DE SERIES Y CANTIDAD A ENVIAR INGRESADA.
      $form = $event->getForm();
      $quantity = $form->get('quantity')->getData();
      $series = $form->get('series')->getData();
      if ($series !== null) {
        $seriesArray = array_filter(array_map('trim', explode(',', $series))); // Separar por comas
        if (count($seriesArray) !== $quantity) {
          $form->get('serie')->addError(new FormError("Debe ingresar la cantidad indicada de series separadas por ','."));
        }
      }
      // EVALUAR SI LA CANTIDAD SUPERA EL STOCK ACTUAL
      $product = $form->get('product')->getData();
      // if ($product->getQuantity() < $quantity){
      //   $form->get('product')->addError(new FormError('La cantidad ingresada del ' . $product->getType() . $product->getBrand() . ','. $product->getModel() .  'supera el stock actual.'));
      // }
    });
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Detail::class,
      'available_products' => [], 
    ]);
  }
}

// VER VALIDACIONES DE STOCK, DISMINUIR STOCK, PODER OCULTAR PRODUCTOS CON STOCK 0, EVALUAR CANTIDAD DE SERIES INGRESADAS SEGÚN EL NUMERO EN EL INPUT
// DE CANTIDAD.