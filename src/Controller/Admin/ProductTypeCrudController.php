<?php

namespace App\Controller\Admin;

use App\Entity\ProductType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductType::class;
    }
    // Actualizo títulos de las páginas.
    public function configureCrud(Crud $crud): Crud
    {
      return $crud->showEntityActionsInlined()
        ->setPageTitle('index', 'Tipos de producto')
        ->setPageTitle('new', 'Agregar tipo de Producto')
        ->setPageTitle('edit', 'Editar tipo');
    }

    // Campos a mostrar en las distintas páginas.
    public function configureFields(string $pageName): iterable
    {
        return [
            // IdField::new('id'),
            TextField::new('name')->setLabel('Tipo Producto')->setHelp('Mouse, Monitor, etc.')
        ];
    }

    // Configuro acciones.
    public function configureActions(Actions $actions): Actions
    {
      return $actions
        ->update(Crud::PAGE_INDEX, Action::NEW, function(Action $action){
          return $action->setIcon('fa fa-plus')->addCssClass('btn btn-primary')->setLabel('Agregar Tipo')->linkToCrudAction('new');
        })
        ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, function(Action $action){
          return $action->setIcon('fa fa-edit')->addCssClass('btn btn-success')->setLabel('Agregar y seguir creando');
        })
        ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function(Action $action){
          return $action->setIcon('fa fa-edit')->addCssClass('btn btn-primary')->setLabel('Editar');
        })
        ->remove(Crud::PAGE_EDIT,Action::SAVE_AND_CONTINUE)
        ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, function(Action $action){
          return $action->setIcon('fa fa-plus')->addCssClass('btn btn-primary')->setLabel('Agregar');
        });
    }
}
