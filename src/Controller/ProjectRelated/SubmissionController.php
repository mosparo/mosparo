<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\QueryBuilder;
use Mosparo\Entity\Submission;
use Mosparo\Verification\GeneralVerification;
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
            ->add('id', TextColumn::class, ['label' => 'submission.list.id'])
            ->add('page', TwigColumn::class, [
                'label' => 'submission.list.page',
                'propertyPath' => 'submitToken.pageTitle',
                'template' => 'project_related/submission/list/_page.html.twig'
            ])
            ->add('data', TwigColumn::class, [
                'label' => 'submission.list.ipAddress',
                'template' => 'project_related/submission/list/_ipAddress.html.twig'
            ])
            ->add('spam', TwigColumn::class, [
                'label' => 'submission.list.spam',
                'template' => 'project_related/submission/list/_spam.html.twig',
                'className' => 'text-center border-left spam-column'
            ])
            ->add('spamRating', TwigColumn::class, [
                'label' => 'submission.list.spamRating',
                'template' => 'project_related/submission/list/_spamRating.html.twig',
                'className' => 'text-center spam-column'
            ])
            ->add('spamDetectionRating', TextColumn::class, ['visible' => false])
            ->add('submittedAt', TwigColumn::class, [
                'label' => 'submission.list.submittedAt',
                'template' => 'project_related/submission/list/_date.html.twig',
                'className' => 'text-center spam-column'
            ])
            ->add('valid', TwigColumn::class, [
                'label' => 'submission.list.valid',
                'template' => 'project_related/submission/list/_valid.html.twig',
                'className' => 'text-center border-left verification-column'
            ])
            ->add('verifiedAt', TwigColumn::class, [
                'label' => 'submission.list.verifiedAt',
                'template' => 'project_related/submission/list/_date.html.twig',
                'className' => 'text-center border-right verification-column'
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'submission.list.actions',
                'className' => 'buttons',
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
    public function view(Submission $submission): Response
    {
        $minimumTimeActive = $submission->getProject()->getConfigValue('minimumTimeActive');
        $args = ['minimumTimeActive' => $minimumTimeActive];
        if ($minimumTimeActive) {
            $minimumTimeGv = $submission->getGeneralVerification(GeneralVerification::MINIMUM_TIME);

            $args['minimumTimeGv'] = $minimumTimeGv;
        }

        return $this->render('project_related/submission/view.html.twig', [
            'submission' => $submission,
            'generalVerifications' => $submission->getGeneralVerifications(),
        ] + $args);
    }
}
