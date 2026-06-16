<?php

namespace Mosparo\Controller\Administration;

use Doctrine\ORM\QueryBuilder;
use Mosparo\DataTable\MosparoDataTableFactory;
use Mosparo\Entity\CleanupStatistic;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration/cleanup-statistic')]
class CleanupStatisticController extends AbstractController
{
    #[Route('/', name: 'administration_cleanup_statistic')]
    public function index(Request $request, MosparoDataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create(['autoWidth' => true])
            ->add('dateTime', TwigColumn::class, [
                'label' => 'administration.cleanupStatistic.list.dateTime',
                'template' => 'administration/cleanup_statistic/list/_dateTime.html.twig',
            ])
            ->add('cleanupExecutor', TwigColumn::class, [
                'label' => 'administration.cleanupStatistic.list.cleanupExecutor',
                'template' => 'administration/cleanup_statistic/list/_cleanupExecutor.html.twig',
            ])
            ->add('numberOfStoredSubmitTokens', TwigColumn::class, [
                'label' => 'administration.cleanupStatistic.list.submitTokens',
                'template' => 'administration/cleanup_statistic/list/_submitTokens.html.twig'
            ])
            ->add('numberOfStoredSubmissions', TwigColumn::class, [
                'label' => 'administration.cleanupStatistic.list.submissions',
                'template' => 'administration/cleanup_statistic/list/_submissions.html.twig'
            ])
            ->add('executionTime', TwigColumn::class, [
                'label' => 'administration.cleanupStatistic.list.executionTime',
                'template' => 'administration/cleanup_statistic/list/_executionTime.html.twig',
            ])
            ->add('cleanupStatus', TwigColumn::class, [
                'label' => 'administration.cleanupStatistic.list.cleanupStatus',
                'template' => 'administration/cleanup_statistic/list/_cleanupStatus.html.twig',
            ])
            ->addOrderBy('dateTime', DataTable::SORT_DESCENDING)
            ->createAdapter(ORMAdapter::class, [
                'entity' => CleanupStatistic::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('e')
                        ->from(CleanupStatistic::class, 'e');
                },
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('administration/cleanup_statistic/list.html.twig', [
            'datatable' => $table
        ]);
    }
}
