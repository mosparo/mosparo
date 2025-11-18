<?php

namespace Mosparo\DataTable;

use Mosparo\Helper\InterfaceHelper;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\DataTableRendererInterface;
use Omines\DataTablesBundle\DependencyInjection\Instantiator;
use Omines\DataTablesBundle\Exporter\DataTableExporterManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MosparoDataTableFactory extends DataTableFactory
{
    public function __construct(
        array $config,
        DataTableRendererInterface $renderer,
        Instantiator $instantiator,
        EventDispatcherInterface $eventDispatcher,
        DataTableExporterManager $exporterManager,
        InterfaceHelper $interfaceHelper,
        RequestStack $requestStack
    ) {
        $config['options']['pageLength'] = $interfaceHelper->determineNumberOfItemsPerPage($requestStack->getMainRequest());

        parent::__construct($config, $renderer, $instantiator, $eventDispatcher, $exporterManager);
    }
}