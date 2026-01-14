<?php

/**
 * Point d'entrée central pour la configuration et le chargement
 * de tous les composants et bundles de l'application.
 *
 * Gère le cycle de vie des requêtes HTTP et orchestre
 * le fonctionnement global de l'application.
 */

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
