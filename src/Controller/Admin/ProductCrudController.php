<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class ProductCrudController extends AbstractCrudController
{
  private EntityManagerInterface $em;
  public function __construct(EntityManagerInterface $em)
  {
    // Seteo entityManager para acceder más facilmente.
    $this->em = $em;
  }
  public static function getEntityFqcn(): string
  {
    return Product::class;
  }

  public function configureCrud(Crud $crud): Crud
  {
    // Actualiza títulos en páginas.
    return $crud->showEntityActionsInlined(false)
      ->setPageTitle('new', 'Agregar Producto')
      ->setPageTitle('edit', 'Modificar Producto')
      ->setPageTitle('index', 'Inventario');
  }

  // Esta funcion sobreescribe la query que se hace para mostrar el listado en el index.
  // Si no existe ningún filtro aplicado, traigo únicamente aquellos productos que no están ocultos.
  // Si el filtro de hidden está activado, entonces traigo únicamente aquellos que sí lo están.
  public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
  {
    // Obtengo filtros aplicados y construyo la query.
    $appliedFilters = $searchDto->getAppliedFilters();
    $qb = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
    if (isset($appliedFilters['hidden'])) {
      $qb->orderBy('entity.hidden', 'DESC');
    } else {
      // Si no se aplica el filtro, solo mostrar productos no ocultos
      $qb->andWhere('entity.hidden = :hidden')
        ->setParameter('hidden', false);
    }
    return $qb;
  }

  public function configureActions(Actions $actions): Actions
  {
    // Creo y configuro acciones.
    // Creo la acción Mostrar, cambia el estado de la entidad y recarga la página.
    // Se muestra únicamente cuando el stock del producto es 0 y ya está oculto.
    $mostrar = Action::new('hidden', 'Mostrar', 'fa-solid fa-eye')
    ->displayAsLink()
    ->displayIf(static function ($entity) {
      return $entity->getQuantity() == 0 && $entity->isHidden();
    })
    ->linkToCrudAction('toggleHidden');
    
    // Creo la acción Ocultar, cambia el estado de la entidad y recarga la página.
    // La diferencia con el switch del index es que éste recarga la página al clickearlo, el switch nativo del index permite
    //  cambiar múltiples estados y no se ve reflejado en el listado hasta recargar la página manualmente.
    // Se muestra únicamente cuando el stock del producto es 0 y no se encuentra oculto. No se puede ocultar algo que sí se tiene, es para mantener "limpia" la información.
    $ocultar = Action::new('show', 'Ocultar', 'fa-solid fa-eye-slash')
      ->displayAsLink()
      ->displayIf(static function ($entity) {
          return $entity->getQuantity() == 0 && !$entity->isHidden();
      })
      ->linkToCrudAction('toggleHidden');


    return $actions
      // Cambios en página Index.
      ->update(Crud::PAGE_INDEX, Action::NEW , function (Action $action) {
        return $action->setIcon('fa fa-plus')->addCssClass('btn btn-primary')->setLabel('Agregar al Inventario');
      })
      ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
        return $action->setLabel('Editar')->setIcon('fa-regular fa-pen-to-square');
      })
      //  Se agregan las acciones "Ocultar" y "Mostrar"
      ->add(Crud::PAGE_INDEX, $ocultar)
      ->add(Crud::PAGE_INDEX, $mostrar)
      // Se quita el botón de eliminar. Es mejor que los productos queden guardados y en todo caso actualizar el stock.
      // Éstos pueden estar relacionados a distintos detalles. En un futuro puede implementarse un boton de eliminar que se haga cargo también de
      //  eliminar en cascada.
      ->remove(Crud::PAGE_INDEX, Action::DELETE)
      // Cambios en página New.
      ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, function (Action $action) {
        return $action->setIcon('fa fa-edit')->addCssClass('btn btn-success')->setLabel('Agregar y seguir creando');
      })
      ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, function (Action $action) {
        return $action->setIcon('fa fa-plus')->addCssClass('btn btn-primary')->setLabel('Agregar');
      })
      // Cambios en página Edit.
      ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
        return $action->setLabel('Guardar y seguir editando');
      })
      ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function (Action $action) {
        return $action->setLabel('Guardar');
      });
  }


  public function configureFields(string $pageName): iterable
  {
    // Se definen los campos de la entidad a mostrar en CRUD.
    return [
      TextField::new('formattedTypeAndBrand', 'Producto')->onlyOnIndex(),
      AssociationField::new('type')->setLabel('Tipo')->onlyWhenUpdating()->onlyWhenCreating(),
      AssociationField::new('brand')->setLabel('Marca')->onlyWhenUpdating()->onlyWhenCreating(),
      TextField::new('model')->setLabel('Modelo'),
      IntegerField::new('quantity')->setLabel('Cantidad'),
      TextField::new('location')->setLabel('Ubicacion'),
      TextField::new('observations')->setLabel('Observaciones')->hideOnIndex(),
      BooleanField::new('hidden')->renderAsSwitch()->setLabel('Oculto')->setSortable(false)->onlyOnDetail()
    ];
  }
  // Se configuran los filtros de la entidad en Index. Se agrega el filtro que permita buscar por "Oculto".
  public function configureFilters(Filters $filters): Filters
  {
    return $filters
      ->add('model')
      ->add('type')
      ->add('brand')
      ->add('location')
      ->add(
        BooleanFilter::new('hidden')->setLabel('Mostrar ocultos')
          ->setFormTypeOption('expanded', true)
      );
  }

  // Togglea el estado de 'hidden' en la entidad y recarga la página.
  public function toggleHidden(AdminContext $adminContext, AdminUrlGenerator $adminUrlGenerator): Response
  {
    $producto = $adminContext->getEntity()->getInstance();
    $producto->setHidden(!($producto->isHidden()));
    $this->em->persist($producto);
    $this->em->flush();

    // Genera la URL pero también limpia los filtros aplicados para renderizar la tabla de forma default.
    $url = $adminUrlGenerator->unset('filters')->setController(ProductCrudController::class)
      ->setAction(Action::INDEX)
      ->generateUrl();

    return $this->redirect($url);
  }

  // Cambia el estado 'hidden' automáticamente a FALSE al editar el producto y ponerle un stock válido.
  public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
  {
    if ($entityInstance->getQuantity() > 0) {
      $entityInstance->setHidden(false);
    }
    $entityManager->persist($entityInstance);
    $entityManager->flush();
  }

  public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Se setea un valor por defecto en el campo 'oculto'.
        $entityInstance->setHidden(false);
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }
}