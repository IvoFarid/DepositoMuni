<?php

namespace App\Controller\Admin;

use App\Entity\Directions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DirectionsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Directions::class;
    }
    // Cambios de títulos en las páginas.
    public function configureCrud(Crud $crud): Crud
    {
      return $crud->showEntityActionsInlined()
        ->setPageTitle('new', 'Agregar Dirección de destino')
        ->setPageTitle('index', 'Destinos')
        ->setPageTitle('edit', 'Editar Dirección')
        ->setPageTitle('detail', 'Dirección');
    }
    // Define los campos a mostrar en páginas index y new.
    public function configureFields(string $pageName): iterable
    {
        // setHelp define un texto gris debajo del input a modo de ejemplo.
        return [
            TextField::new('name')->setLabel('Direccion')->setHelp('Direccion General de Compras y Suministros, etc.')
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
      return $actions
        // Se hace update de la acción new en la página index; y tanto agregar y seguir creando como guardar y volver en la página new.
        ->update(Crud::PAGE_INDEX, Action::NEW, function(Action $action){
          return $action->setIcon('fa fa-plus')->addCssClass('btn btn-primary')->setLabel('Agregar Dirección')->linkToCrudAction('new');
        })
        ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, function(Action $action){
          return $action->setIcon('fa fa-edit')->addCssClass('btn btn-success')->setLabel('Agregar y seguir creando');
        })
        ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, function(Action $action){
          return $action->setIcon('fa fa-plus')->addCssClass('btn btn-primary')->setLabel('Agregar');
        });
    }
}
