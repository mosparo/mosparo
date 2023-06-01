<?php

namespace Mosparo;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    const MAJOR_VERSION = '0.3';

    const VERSION = '0.3.20';

    use MicroKernelTrait;
}
