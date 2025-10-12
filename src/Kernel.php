<?php

namespace Mosparo;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    const MAJOR_VERSION = '1.4';

    const VERSION = '1.4.2';

    use MicroKernelTrait;
}
