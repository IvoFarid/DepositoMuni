<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use IntlDateFormatter;

class DateFormatter extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('fecha_localizada', [$this, 'formatearFecha']),
        ];
    }

    public function formatearFecha(\DateTimeInterface $fecha = null, string $locale = 'es_ES', string $formato = 'default'): string
    { 
      if ($fecha === null) {
        return 'No se recibió'; // Valor predeterminado si la fecha es null
      }

      $formatos = [
        'default' => 'EEEE, d MMMM Y HH:mm a', // Formato completo con nombres de días y meses
        'numerico' => 'd/MM/yyyy HH:mm', // Formato con solo números (día/mes/año hora:minuto)
      ];

      $formatoSeleccionado = $formatos[$formato] ?? $formatos['default'];

      $formatter = new IntlDateFormatter(
          $locale,
          IntlDateFormatter::FULL,
          IntlDateFormatter::NONE,
          'America/Buenos_Aires',
          IntlDateFormatter::GREGORIAN,
          $formatoSeleccionado
      );
        // $formatter = new IntlDateFormatter(
        //     $locale,
        //     IntlDateFormatter::FULL,
        //     IntlDateFormatter::NONE,
        //     'America/Buenos_Aires',
        //     IntlDateFormatter::GREGORIAN,
        //     'EEEE, d MMMM Y HH:mm a' // Personaliza el formato
        // );

        return $formatter->format($fecha);
    }
}
