<?php

namespace App\Controller\Admin;

use App\Entity\Brand;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BrandCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Brand::class;
    }

    // Cambios de títulos en las páginas.
    public function configureCrud(Crud $crud): Crud
    {
      return $crud->showEntityActionsInlined()
        ->setPageTitle('new', 'Agregar Marca')
        ->setPageTitle('index', 'Marcas')
        ->setPageTitle('edit', 'Editar Marca');
    }
    // Campos a mostrar en las paginas index y new/edit.
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setLabel('Nombre')->setHelp('SONY, Lenovo, etc.'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
      return $actions
        // Se hace update del botón de la página index para agregar marca.
        ->update(Crud::PAGE_INDEX, Action::NEW, function(Action $action){
          return $action->setIcon('fa fa-plus')->addCssClass('btn btn-primary')->setLabel('Agregar Marca')->linkToCrudAction('new');
        })
        // Se hace update del botón de editar y volver en la página edit.
        ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function(Action $action){
          return $action->setIcon('fa fa-edit')->setLabel('Editar')->linkToCrudAction(Action::SAVE_AND_RETURN);
        })
        // Se elimina de la página edit el boton de guardar y seguir editando, no tiene sentido al ser un formulario corto (1 campo).
        ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
        // Se hace update del boton agregar y continuar para poder agregar de forma rápida.
        ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, function(Action $action){
          return $action->setIcon('fa fa-edit')->addCssClass('btn btn-success')->setLabel('Agregar y seguir creando');
        })
        // Se hace update del botón agregar y volver.
        ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, function(Action $action){
          return $action->setIcon('fa fa-plus')->addCssClass('btn btn-primary')->setLabel('Agregar');
        });
    }
}
