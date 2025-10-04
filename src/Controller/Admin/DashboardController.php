<?php

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Entity\Certificate;
use App\Entity\Directions;
use App\Entity\Product;
use App\Entity\ProductType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
      return $this->render('admin/dashboard.html.twig');
    }

    // Se configura el dashboard, darkTheme automático y título establecido.
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Sistema Depósito')
            ->setDefaultColorScheme('dark');
    }

    // Se definen las pestañas en el sidebar de la izquierda junto a su label, controlador y acción asociada a cada botón.
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Inicio', 'fa fa-home')->setCssClass('my-1');
        yield MenuItem::linkToCrud('Inventario', 'fa-solid fa-computer', Product::class)->setCssClass('my-1');
        yield MenuItem::linkToCrud('Constancias', 'fa-solid fa-copy', Certificate::class)->setCssClass('my-1');
        yield MenuItem::subMenu('Otras Acciones', 'fas fa-bars')->setSubItems([
          MenuItem::linkToCrud('Marcas', 'fa-solid fa-signature', Brand::class)->setAction('index'),
          MenuItem::linkToCrud('Tipos de producto', 'fa-solid fa-server', ProductType::class)->setAction('index'),
          MenuItem::linkToCrud('Destinos', 'fas fa-sitemap', Directions::class)->setAction('index'),
        ]);
    }
}
