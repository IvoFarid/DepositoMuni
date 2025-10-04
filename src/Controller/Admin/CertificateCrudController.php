<?php

namespace App\Controller\Admin;

use App\Entity\Certificate;
use App\Entity\Detail;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\PDFGeneratorService;
use App\Form\ConstanciaForm;
use App\Twig\DateFormatter;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CertificateCrudController extends AbstractCrudController
{
  private EntityManagerInterface $em;
  private DateFormatter $df;
  public function __construct(EntityManagerInterface $em, DateFormatter $df)
  {
    // Seteo entityManager para acceder más facilmente.
    $this->em = $em;
    $this->df = $df;
  }
  // Función para configurar filtros, no cambia nada con respecto al comportamiento default. Quedó porque en algún momento fue necesario agregar algún campo.
  public function configureFilters(Filters $filters): Filters
  {
    return $filters;
  }

  public static function getEntityFqcn(): string
  {
    return Certificate::class;
  }

  // Cambio de títulos en las páginas.
  public function configureCrud(Crud $crud): Crud
  {
    return $crud->showEntityActionsInlined()
      ->setPageTitle('index', 'Listado Constancias')
      ->setPageTitle('edit', 'Constancia');
  }

  // Configura los campos a mostrar en página index.
  public function configureFields(string $pageName): iterable
  {
    return [
      DateField::new('date')->setLabel('Fecha')->formatValue(function($value){
        return $this->df->formatearFecha($value, 'es_ES', 'numerico');
      }),
      DateField::new('receiptDate')->setLabel('Fecha recepción')->formatValue(function ($value) {
        // return $value ? $value->format('d/m/Y') : 'No se recibió';
        return $this->df->formatearFecha($value, 'es_ES', 'numerico');
      }),
      TextField::new('initiator')->setLabel('Responsable'),
      AssociationField::new('sendedTo')->setLabel('Enviado a'),
    ];
  }

  public function configureActions(Actions $actions): Actions
  {
    // Se crea la acción custom de descargar, se asocia con la función "download"
    $download = Action::new('download', null, 'fa-solid fa-download')
      ->displayAsLink()
      ->setLabel('')
      ->linkToCrudAction('download');
    // se crea la opcion de ver detalle.
    $detail = Action::new('detalle', 'Ver detalle', 'fa-solid fa-eye')
    ->displayAsLink()
    ->setLabel('')
    ->linkToCrudAction('verConstancia');

    // Updates de acciones en las distintas páginas.
    return $actions
      ->update(Crud::PAGE_INDEX, Action::NEW , function (Action $action) {
        return $action->setIcon('fa fa-plus')->addCssClass('btn btn-primary')->setLabel('Generar Constancia')->linkToCrudAction('generarConstancia');
      })
      ->update(Crud::PAGE_INDEX, Action::EDIT , function (Action $action) {
        return $action->setIcon('fa fa-edit')->displayAsLink()->setLabel('');
      })
      ->add(Crud::PAGE_INDEX, $detail)
      // ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
      //   return $action->setIcon('fa fa-eye')->addCssClass('me-2')->setLabel('Ver Detalle')->displayAsLink()->linkToCrudAction('verConstancia');
      // })
      ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
        return $action->setIcon('fa fa-trash')->addCssClass('')->setLabel(false);
      })
      // Se agrega en el index la acción de descarga.
      ->add(Crud::PAGE_INDEX, $download);
  }

  // Se define la funcion download
  public function download(AdminContext $adminContext, ProductRepository $productRepository, PDFGeneratorService $PDFG): Response
  {
    // Del adminContext se consigue la instancia del certificado como del producto asociado para poder dibujar el PDF correctamente.
    $instance = $adminContext->getEntity()->getInstance();
    $details = $instance->getDetails();
    // Se genera el filename y se usa el servicio para crear PDF.
    $filename = $instance->getDate()->format('Y-m-d') . '-' . $instance->getSendedTo();
    return $PDFG->generatePdf(
      'admin/pdf_template.html.twig',
      [
        'constancia' => $instance,
        'details' => $details
      ],
      $filename
    );
  }

  public function generarConstancia(AdminContext $adminContext, Request $request, AdminUrlGenerator $adminUrlGenerator, PDFGeneratorService $PDFG)
  {
    $constancia = new Certificate();
    $constancia->setDate(new \DateTime());
    $indexUrl = $adminUrlGenerator
        ->setController(CertificateCrudController::class)
        ->setAction('index')
        ->generateUrl();
    // Obtener productos de la base de datos que posean stock válido.
    $products = $this->em->getRepository(Product::class)->findAvailableProducts();
    // Crea un detalle vacío y lo añade al certificado para poder inicialmente renderizar un primer conjunto de inputs de forma default en el twig.
    $detail = new Detail();
    $constancia->addDetail($detail);

    // Se crea el formulario y se envía como productos seleccionables aquellos resultantes de la consulta anterior.
    $form = $this->createForm(ConstanciaForm::class, $constancia, [
      'available_products' => $products
    ]);
    
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $details = $constancia->getDetails();
      foreach ($details as $detail) {
        // Por cada detalle acutalizo stocks del producto en cuestión y los agrego a la constancia.
        // Las validaciones no se hacen acá, están en el propio formulario con el evento post_submit.
        $product = $detail->getProduct();
        $product->subStock($detail->getQuantity());
        $constancia->addDetail($detail);
        // Se persiste cada detalle.
        $this->em->persist($detail);
      }
      // Se persiste la constancia y luego se guarda todo.
      $this->em->persist($constancia);
      $this->em->flush();

      $filename = $constancia->getDate()->format('Y-m-d') . '-' . $constancia->getSendedTo();
      return $PDFG->generatePdf(
        'admin/pdf_template.html.twig',
        [
          'constancia' => $constancia,
          'details' => $details
        ],
        $filename
      );
    }
    return $this->render('admin/constancia/generarCertificado.html.twig', [
      'form' => $form->createView(),
      'indexUrl' => $indexUrl
    ]);
  }

  public function verConstancia(AdminContext $adminContext, AdminUrlGenerator $adminUrlGenerator)
  {
    // Se obtiene la instancia de la constancia seleccionada.
    $constancia = $adminContext->getEntity()->getInstance();
    $details = $constancia->getDetails();
    // Se genera la URL para la tag <a>'Volver'</a> del twig.
    $indexUrl = $adminUrlGenerator
        ->setController(CertificateCrudController::class)
        ->setAction('index')
        ->generateUrl();
    // Se genera la URL para la tag <a>'Imprimir'</a> del twig.
    $downloadUrl = $adminUrlGenerator->setController(CertificateCrudController::class)
    ->setAction('download')
    ->generateUrl();

    return $this->render('admin/constancia/mostrarCertificado.html.twig', [
      'details' => $details,
      'constancia' => $constancia,
      'indexUrl' => $indexUrl,
      'downloadUrl' => $downloadUrl
    ]);
  }
}