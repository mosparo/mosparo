<?php

namespace Mosparo;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    const VERSION = '0.2.17';

    use MicroKernelTrait;
}
