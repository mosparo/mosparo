<?php

namespace Mosparo\Controller\ProjectRelated;

use Mosparo\Entity\Submission;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/submissions")
 */
class SubmissionController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    /**
     * @Route("/", name="submission_list")
     */
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create(['autoWidth' => true])
            ->add('id', TextColumn::class, ['label' => 'ID'])
            ->add('ipAddress', TextColumn::class, ['label' => 'IP address'])
            ->add('spamRating', TextColumn::class, ['label' => 'Spam rating'])
            ->add('actions', TwigColumn::class, [
                'label' => 'Actions',
                'className' => 'buttons',
                'template' => 'project_related/submission/list/_actions.html.twig'
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Submission::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('project_related/submission/list.html.twig', [
            'datatable' => $table
        ]);
    }

    /**
     * @Route("/{id}/show", name="submission_show")
     */
    public function show(Request $request, Submission $submission): Response
    {

    }
}
