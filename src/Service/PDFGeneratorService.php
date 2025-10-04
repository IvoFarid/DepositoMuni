<?php
namespace App\Service;

use Dompdf\Dompdf;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PDFGeneratorService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function generatePDF(string $template, array $data, string $filename, bool $download = true): Response
    {
        $dompdf = new Dompdf();
        $html = $this->twig->render($template, $data);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return new Response(
            $dompdf->stream($filename . '.pdf', ["Attachment" => $download]), 
            Response::HTTP_OK, 
            ['Content-type' => 'application/pdf']
        );
    }
}