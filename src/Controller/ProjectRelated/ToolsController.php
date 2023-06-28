<?php

namespace Mosparo\Controller\ProjectRelated;

use Mosparo\Helper\RuleTesterHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tools")
 */
class ToolsController extends AbstractController implements ProjectRelatedInterface
{
    use ProjectRelatedTrait;

    /**
     * @Route("/rule-tester", name="tools_rule_tester")
     */
    public function ruleTester(Request $request, RuleTesterHelper $ruleTesterHelper): Response
    {
        $typeChoices = [
            'tools.ruleTester.types.textField' => 'textField',
            'tools.ruleTester.types.textarea' => 'textarea',
            'tools.ruleTester.types.emailField' => 'emailField',
            'tools.ruleTester.types.urlField' => 'urlField',
            'tools.ruleTester.types.userAgent' => 'userAgent',
            'tools.ruleTester.types.ipAddress' => 'ipAddress',
        ];
        $data = [
            'type' => 'textField',
            'useRules' => true,
            'useRulesets' => true,
        ];
        $form = $this->createFormBuilder($data, ['translation_domain' => 'mosparo'])
            ->add('value', TextareaType::class, [
                'label' => 'tools.ruleTester.form.value',
                'attr' => [
                    'data-bs-toggle' => 'autosize'
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'tools.ruleTester.form.type',
                'choices' => $typeChoices,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('useRules', CheckboxType::class, [
                'label' => 'tools.ruleTester.form.useRules',
                'required' => false,
            ])
            ->add('useRulesets', CheckboxType::class, [
                'label' => 'tools.ruleTester.form.useRulesets',
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);

        $testData = [
            'value' => '',
            'type' => '',
            'useRules' => '',
            'useRulesets' => ''
        ];
        $submission = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $value = trim($form->get('value')->getData());
            $type = $form->get('type')->getData();
            $useRules = $form->get('useRules')->getData();
            $useRulesets = $form->get('useRulesets')->getData();

            $submission = $ruleTesterHelper->simulateRequest($value, $type, $useRules, $useRulesets);

            $testData = [
                'value' => $value,
                'type' => $type,
                'useRules' => $useRules,
                'useRulesets' => $useRulesets,
            ];
        }

        return $this->render('project_related/tools/rule_tester.html.twig', [
            'form' => $form->createView(),
            'submission' => $submission,
            'testData' => $testData,
        ]);
    }
}