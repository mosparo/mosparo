<?php

namespace Mosparo\Controller\ProjectRelated;

use Doctrine\ORM\EntityManagerInterface;
use Mosparo\DataTable\MosparoDataTableFactory;
use Mosparo\Entity\SubmissionRule;
use Mosparo\Form\SubmissionRuleConfigValueFormType;
use Mosparo\Rules\SubmissionRule\SubmissionRuleManager;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TwigColumn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/project/{_projectId}/rules/submission-rules')]
class SubmissionRuleController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    protected EntityManagerInterface $entityManager;

    protected TranslatorInterface $translator;

    protected SubmissionRuleManager $submissionRuleManager;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator, SubmissionRuleManager $submissionRuleManager)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->submissionRuleManager = $submissionRuleManager;
    }

    #[Route('/', name: 'rules_submission_rule_list')]
    public function index(Request $request, MosparoDataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create(['autoWidth' => true])
            ->add('name', TwigColumn::class, [
                'label' => 'rules.submissionRule.list.name',
                'template' => 'project_related/rules/submission_rule/list/_name.html.twig'
            ])
            ->add('enabled', TwigColumn::class, [
                'label' => 'rules.submissionRule.list.status',
                'template' => 'project_related/rules/submission_rule/list/_status.html.twig'
            ])
            ->add('actions', TwigColumn::class, [
                'label' => 'rules.submissionRule.list.actions',
                'className' => 'buttons',
                'template' => 'project_related/rules/submission_rule/list/_actions.html.twig'
            ])
            ->addOrderBy('name')
            ->createAdapter(ArrayAdapter::class, $this->getRulesArray())
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('project_related/rules/submission_rule/list.html.twig', [
            'datatable' => $table,
        ]);
    }

    #[Route('/configure/{key}', name: 'rules_submission_rule_configure')]
    public function configure(Request $request, MosparoDataTableFactory $dataTableFactory, string $key): Response
    {
        $submissionRule = $this->submissionRuleManager->getRule($key);
        if (!$submissionRule) {
            return $this->redirectToRoute('rules_submission_rule_list');
        }

        $isNew = false;
        $submissionRuleRepository = $this->entityManager->getRepository(SubmissionRule::class);
        $storedSubmissionRule = $submissionRuleRepository->findOneBy(['key' => $key]);
        if (!$storedSubmissionRule) {
            $storedSubmissionRule = (new SubmissionRule())
                ->setKey($submissionRule->getKey())
                ->setProject($this->getActiveProject())
            ;
            $submissionRule->setDefaultSettings($storedSubmissionRule);
            $isNew = true;
        }

        $formBuilder = $this->createFormBuilder($storedSubmissionRule, ['translation_domain' => 'mosparo'])
            ->add('enabled', CheckboxType::class, ['label' => 'rules.submissionRule.form.enableRule', 'required' => false])
            ->add('configValues', SubmissionRuleConfigValueFormType::class, ['submissionRule' => $submissionRule])
            ->add('rating', NumberType::class, [
                'label' => 'rules.submissionRule.form.rating',
                'required' => true,
                'help' => 'rules.submissionRule.form.ratingHelp',
                'html5' => true,
                'scale' => 1,
                'attr' => [
                    'min' => 0.1,
                    'step' => 'any',
                    'class' => 'text-end',
                ]
            ])
            ->add('submitted', HiddenType::class, ['mapped' => false, 'data' => 1]) // Dummy field, otherwise, the form would not save when the rule is disabled
        ;

        $form = $formBuilder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Save the settings
            if ($isNew) {
                $this->entityManager->persist($storedSubmissionRule);
            }

            if (!$storedSubmissionRule->isEnabled()) {
                $submissionRule->setDefaultSettings($storedSubmissionRule);
            }

            $this->entityManager->flush();

            $session = $request->getSession();
            $session->getFlashBag()->add(
                'success',
                $this->translator->trans(
                    'rules.submissionRule.form.message.successfullySaved',
                    [],
                    'mosparo'
                )
            );

            return $this->redirectToRoute('rules_submission_rule_list', ['_projectId' => $this->getActiveProject()->getId()]);
        }

        return $this->render('project_related/rules/submission_rule/form.html.twig', [
            'submissionRule' => $submissionRule,
            'form' => $form->createView(),
        ]);
    }

    public function getRulesArray(): array
    {
        $submissionRuleRepository = $this->entityManager->getRepository(SubmissionRule::class);
        $storedSubmissionRules = $submissionRuleRepository->findAll();

        $rulesArray = [];
        foreach ($this->submissionRuleManager->getRules() as $rule) {
            $storedSubmissionRule = null;
            foreach ($storedSubmissionRules as $sr) {
                if ($sr->getKey() === $sr->getKey()) {
                    $storedSubmissionRule = $sr;
                    break;
                }
            }

            $rulesArray[] = [
                'key' => $rule->getKey(),
                'name' => $rule->getName(),
                'summary' => $rule->getSummary(),
                'enabled' => ($storedSubmissionRule !== null) ? $storedSubmissionRule->isEnabled() : false,
            ];
        }

        return $rulesArray;
    }
}
