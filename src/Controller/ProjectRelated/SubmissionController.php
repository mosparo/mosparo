<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\Submission;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
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
            ->add('page', TwigColumn::class, [
                'label' => 'Page',
                'propertyPath' => 'submitToken.pageTitle',
                'template' => 'project_related/submission/list/_page.html.twig'
            ])
            ->add('data', TwigColumn::class, [
                'label' => 'IP Address',
                'template' => 'project_related/submission/list/_ipAddress.html.twig'
            ])
            ->add('spam', TwigColumn::class, [
                'label' => 'SPAM',
                'template' => 'project_related/submission/list/_spam.html.twig',
                'className' => 'text-center border-left'
            ])
            ->add('spamRating', TwigColumn::class, [
                'label' => 'Spam rating',
                'template' => 'project_related/submission/list/_spamRating.html.twig',
                'className' => 'text-center'
            ])
            ->add('spamDetectionRating', TextColumn::class, ['visible' => false])
            ->add('submittedAt', TwigColumn::class, [
                'label' => 'Submitted at',
                'template' => 'project_related/submission/list/_date.html.twig',
                'className' => 'text-center'
            ])
            ->add('valid', TwigColumn::class, [
                'label' => 'Valid',
                'template' => 'project_related/submission/list/_valid.html.twig',
                'className' => 'text-center border-left'
            ])
            ->add('verifiedAt', TwigColumn::class, [
                'label' => 'Verified at',
                'template' => 'project_related/submission/list/_date.html.twig',
                'className' => 'text-center border-right'
            ])
            ->add('submitToken', TwigColumn::class, [
                'label' => 'Actions',
                'className' => 'text-center',
                'template' => 'project_related/submission/list/_actions.html.twig',
            ])
            ->addOrderBy('submittedAt', DataTable::SORT_DESCENDING)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Submission::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('e')
                        ->from(Submission::class, 'e')
                        ->where('e.spam = 1')
                        ->orWhere('e.valid IS NOT NULL');
                },
            ])
            ->handleRequest($request);

        $config = $this->getParameter('datatables.config');
        $table->setTemplate('project_related/submission/list/_table.html.twig', $config['template_parameters']);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('project_related/submission/list.html.twig', [
            'datatable' => $table
        ]);
    }

    /**
     * @Route("/{id}/view", name="submission_view")
     */
    public function view(Request $request, Submission $submission): Response
    {
        return $this->render('project_related/submission/view.html.twig', [
            'submission' => $submission
        ]);
    }
}
