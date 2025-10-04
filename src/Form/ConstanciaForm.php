<?php
// use App\Entity\Product;
// use Symfony\Component\Form\AbstractType;
// use Symfony\Component\Form\Extension\Core\Type\SubmitType;
// use Symfony\Component\Form\Extension\Core\Type\CollectionType;
// use Symfony\Component\Form\FormBuilderInterface;
// use Symfony\Component\OptionsResolver\OptionsResolver;
// use App\Entity\Certificate; // La entidad padre

// class ConstanciaForm extends AbstractType
// {
//     public function buildForm(FormBuilderInterface $builder, array $options)
//     {
//         $builder
//             ->add('products', CollectionType::class, [
//                 'entry_type' => ProductosForm::class,
//                 'entry_options' => ['products' => $options['products']], // Pasar los productos
//                 'allow_add' => true,  // Permite agregar dinámicamente
//                 'allow_delete' => true, // Permite eliminar dinámicamente
//                 'by_reference' => false
//             ])
//             ->add('submit', SubmitType::class, ['label' => 'Enviar pedido']);
//     }

//     public function configureOptions(OptionsResolver $resolver)
//     {
//         $resolver->setDefaults([
//             'data_class' => Certificate::class, // La entidad Order
//             'products' => [], // Lista de productos disponibles
//         ]);
//     }
// // }
// namespace App\Form;

// use App\Entity\Certificate;
// use App\Form\DetailForm;
// use Symfony\Component\Form\AbstractType;
// use Symfony\Component\Form\Extension\Core\Type\CollectionType;
// use Symfony\Component\Form\Extension\Core\Type\SubmitType;
// use Symfony\Component\Form\FormBuilderInterface;
// use Symfony\Component\OptionsResolver\OptionsResolver;

// class ConstanciaForm extends AbstractType
// {
//     public function buildForm(FormBuilderInterface $builder, array $options)
//     {
//         $builder
//             ->add('details', CollectionType::class, [
//                 'entry_type' => DetailForm::class,
//                 'allow_add' => true,
//                 'allow_delete' => true,
//                 'by_reference' => false
//             ])
//             ->add('save', SubmitType::class, ['label' => 'Generar Constancia']);
//     }

//     public function configureOptions(OptionsResolver $resolver)
//     {
//         $resolver->setDefaults([
//             'data_class' => Certificate::class,
//         ]);
//     }
// }
namespace App\Form;

use App\Entity\Certificate;
use App\Entity\Directions;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Form\DetailForm;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConstanciaForm extends AbstractType
{
  private ProductRepository $pr;
  public function __construct(ProductRepository $pr) {
    $this->pr = $pr;
  }
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    //dd($options);
    $builder
      ->add('sendedTo', EntityType::class, [
        'class' => Directions::class,
        'attr' => ['class' => 'select2Dir'],
        'choice_label' => function (Directions $direction) {
          return $direction->getName();
        }, // Ajusta según la propiedad del producto
        'label' => 'Dirección a enviar:',
        'label_attr' => [
          'class' => 'pb-1 d-block'
        ]
      ])
      ->add('details', CollectionType::class, [
        'entry_type' => DetailForm::class,
        'allow_add' => true,
        'entry_options' => [
          'available_products' => $options['available_products'], // Pasa los productos al formulario de detalle
        ],
        'allow_delete' => true,
        'by_reference' => false,
        'label' => false,
        'prototype' => true,  // ✅ Permite clonar elementos en JS
        'prototype_name' => '__name__', // Nombre dinámico para reemplazo en JS
        // 'attr' => [
        //   'data-prototype' => 'PROTOTYPE_HERE',
        // ],
      ]);

    $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
      $productQuantities = [];
      $form = $event->getForm();
      $details = $form->get('details')->getData();

      // 1. Recorrer los detalles y acumular las cantidades por producto
      foreach ($details as $detail) {
        $productId = $detail->getProduct()->getId();
        $quantity = $detail->getQuantity();
        $productQuantities[$productId] = ($productQuantities[$productId] ?? 0) + $quantity;
      }

      // 2. Validar si la cantidad total acumulada por producto supera el stock
      foreach ($productQuantities as $productId => $totalQuantity) {
        $product = $this->pr->findOneById($productId); // Método para encontrar el producto
        if ($totalQuantity > $product->getQuantity()) {
          $form->get('details')->addError(new FormError('La cantidad ingresada ('. $totalQuantity . ') del ' . $product->getType() .' '. $product->getBrand() . ', '. $product->getModel() . ' supera el stock actual.'));
        }
      }
    });
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
            'data_class' => Certificate::class,  // Asegúrate de que está la clase correcta
            'available_products' => [],  // Define aquí la opción para los productos
        ]);
  }
}
