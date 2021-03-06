<?php

namespace Drupal\mass_decision_tree\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\mass_content_api\DescendantManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a decision tree admin form.
 */
class DecisionTreeAdminForm extends FormBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Drupal\mass_content_api\DescendantManager definition.
   *
   * @var \Drupal\mass_content_api\DescendantManagerInterface
   */
  protected $descendantManager;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DecisionTreeAdminForm.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\mass_content_api\DescendantManagerInterface $descendant_manager
   *   The content api descendant manager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(RendererInterface $renderer, DescendantManagerInterface $descendant_manager, EntityTypeManager $entity_type_manager) {
    $this->renderer = $renderer;
    $this->descendantManager = $descendant_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('descendant_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mass_decision_tree_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = \Drupal::routeMatch()->getParameter('node');

    $form['#attached']['library'][] = 'mass_decision_tree/mass_decision_tree_form';

    // Branch link.
    $branch_url = Url::fromRoute('node.add', ['node_type' => 'decision_tree_branch'], [
      'query' => [
        'destination' => $node->toUrl('canonical')->toString(),
      ],
    ]);
    $branch_link = Link::fromTextAndUrl('Create new decision tree branch', $branch_url)->toRenderable();
    $form['create_branch'] = [
      '#markup' => '<div>' . drupal_render($branch_link) . '</div>',
    ];

    // Conclusion link.
    $conclusion_url = Url::fromRoute('node.add', ['node_type' => 'decision_tree_conclusion'], [
      'query' => [
        'destination' => $node->toUrl('canonical')->toString(),
      ],
    ]);
    $conclusion_link = Link::fromTextAndUrl('Create new decision tree conclusion', $conclusion_url)->toRenderable();
    $form['create_conclusion'] = [
      '#markup' => '<div>' . drupal_render($conclusion_link) . '</div>',
    ];

    $form['table-row'] = [
      '#type' => 'table',
      '#theme' => 'table__system',
      '#attributes' => [
        'id' => 'decisionTree',
      ],
      '#header' => [
        $this->t('Title'),
        $this->t('Type'),
        $this->t('Edit'),
        $this->t('Weight'),
        $this->t('Parent'),
      ],
      '#empty' => $this->t('Sorry, there are no children!'),
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'row-pid',
          'source' => 'row-id',
          'hidden' => TRUE,
          'limit' => FALSE,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'row-weight',
        ],
      ],
    ];

    $children = $this->descendantManager->getChildrenTree($node->id());
    foreach ($children as $child) {
      $info = [
        'child' => $child['id'],
        'depth' => 0,
        'index' => 0,
      ];
      $this->buildRow($form, $info);
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save All Changes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(&$form, &$info) {
    $nid = $info['child'];
    $child_node = $this->entityTypeManager->getStorage('node')->load($nid);

    $info['index']++;
    $types = [
      'decision_tree_branch' => $this->t('Branch'),
      'decision_tree_conclusion' => $this->t('Conclusion'),
    ];

    // TableDrag: Mark the table row as draggable.
    $form['table-row'][$info['index']]['#attributes']['class'][] = 'draggable';

    $form['table-row'][$info['index']]['#attributes']['data-type'] = strtolower($types[$child_node->bundle()]);

    // Add the parent as a data attribute if there is one.
    if (isset($info['parent']) && !empty($info['parent'])) {
      $form['table-row'][$info['index']]['#attributes']['data-parent'] = $info['parent'];
    }

    // Never let conclusions be parents.
    if ($child_node->bundle() === 'decision_tree_conclusion') {
      $form['table-row'][$info['index']]['#attributes']['class'][] = 'tabledrag-leaf';
    }

    // Indent item on load.
    if (isset($info['depth']) && $info['depth'] > 0) {
      $indentation = [
        '#theme' => 'indentation',
        '#size' => $info['depth'],
      ];
    }

    // Title, type, and edit column data.
    $form['table-row'][$info['index']]['title'] = [
      '#markup' => '<a href="' . $child_node->toUrl()->toString() . '" class="decision-tree-form-title" id="' . $nid . '">' . $child_node->label() . '</a>',
      '#prefix' => !empty($indentation) ? drupal_render($indentation) : '',
    ];

    $form['table-row'][$info['index']]['type'] = [
      '#markup' => $types[$child_node->bundle()],
    ];

    $form['table-row'][$info['index']]['edit'] = [
      '#markup' => '<a href="' . $child_node->toUrl('edit-form')->toString() . '">edit</a>',
    ];

    // This is hidden from #tabledrag array (above).
    // TableDrag: Weight column element.
    $form['table-row'][$info['index']]['weight'] = [
      '#parents' => ['table-row', $nid, 'weight'],
      '#type' => 'weight',
      '#title' => $this->t('Weight for ID @id', ['@id' => $nid]),
      '#title_display' => 'invisible',
      '#default_value' => -10,
      // Classify the weight element for #tabledrag.
      '#attributes' => [
        'class' => ['row-weight'],
      ],
    ];
    $form['table-row'][$info['index']]['parent']['id'] = [
      '#parents' => ['table-row', $nid, 'id'],
      '#type' => 'hidden',
      '#value' => $nid,
      '#attributes' => [
        'class' => ['row-id'],
      ],
    ];
    $form['table-row'][$info['index']]['parent']['pid'] = [
      '#parents' => ['table-row', $nid, 'pid'],
      '#type' => 'number',
      '#size' => 3,
      '#min' => 0,
      '#title' => $this->t('Parent ID'),
      '#default_value' => !empty($info['parent']) ? $info['parent'] : '',
      '#attributes' => [
        'class' => ['row-pid'],
      ],
    ];

    // Check for children and pass them in recursively.
    if ($child_node->bundle() === 'decision_tree_branch') {
      foreach ($child_node->field_answers as $answers) {
        $answer = $answers->entity;

        // Use now the entity to get the values you need.
        $true_nid = $answer->field_true_answer_path->target_id;
        if ($true_nid) {
          $info['depth']++;
          $info['child'] = $true_nid;
          $info['parent'] = $nid;
          $this->buildRow($form, $info);
          $info['depth']--;
        }
        $false_nid = $answer->field_false_answer_path->target_id;
        if ($false_nid) {
          $info['depth']++;
          $info['child'] = $false_nid;
          $info['parent'] = $nid;
          $this->buildRow($form, $info);
          $info['depth']--;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rows = $form_state->getValue('table-row');
    $kids = [];
    // Build out all our kids before we loop through them.
    foreach ($rows as $row) {
      if (!$row['pid']) {
        $kids[0][] = $row['id'];
      }
      else {
        $kids[$row['pid']][] = $row['id'];
      }
    }
    // Loop through kids and look for changes.
    $changes = FALSE;
    foreach ($kids as $nid => $kid) {
      if ($nid) {
        $node = Node::load($nid);
        foreach ($node->field_answers as $answers) {
          $answer = $answers->entity;

          // Use now the entity to get the values you need.
          $true_nid = $answer->field_true_answer_path->target_id;
          $false_nid = $answer->field_false_answer_path->target_id;

          if ($kid[0] !== $true_nid) {
            // True answer changed, update it.
            $answers->entity->field_true_answer_path->target_id = $kid[0];
            $answers->entity->save();
            $changes = TRUE;
          }
          if ($kid[1] !== $false_nid) {
            // False answer changed, update it.
            $answers->entity->field_false_answer_path->target_id = $kid[1];
            $answers->entity->save();
            $changes = TRUE;
          }
        }
      }
    }
    if ($changes) {
      drupal_set_message('Your changes have been saved successfully!');
    }
  }

}
