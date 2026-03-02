<?php

declare(strict_types=1);

namespace Drupal\promote_it\Form;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\promote_it\Service\PromoteWeightService;
use Drupal\promote_it\Service\PromoteQueryService;
use Drupal\promote_it\Service\PromoteFieldHelperService;

/**
 * Builds the form for promoting content and setting promotion weights.
 *
 * Provides a drag-and-drop interface for managing which content is promoted
 * to the front page and the order in which it appears.
 *
 * @see \Drupal\promote_it\Service\PromoteQueryService
 * @see \Drupal\promote_it\Service\PromoteWeightService
 */
final class PromoteForm extends FormBase {

  /**
   * Constructs a new PromoteForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\promote_it\Service\PromoteWeightService $promoteWeightService
   *   The promote weight service.
   * @param \Drupal\promote_it\Service\PromoteQueryService $promoteQueryService
   *   The promote query service.
   * @param \Drupal\promote_it\Service\PromoteFieldHelperService $fieldHelper
   *   The field helper service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly PromoteWeightService $promoteWeightService,
    private readonly PromoteQueryService $promoteQueryService,
    private readonly PromoteFieldHelperService $fieldHelper,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('promote_it.promote_weight_service'),
      $container->get('promote_it.promote_query_service'),
      $container->get('promote_it.field_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'promote_it_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Retrieve data using the service.
    $weight_rows = $this->promoteWeightService->getWeightedEntities();

    $form_rows = [];
    $last_weight = 0;

    // Get content sort query to get current nodes.
    $nodes_promoted = $this->promoteQueryService->getPromoted();

    $promote_items = [];
    foreach ($nodes_promoted as $node) {
      $promote_items[$node->id()] = $node->id();
    }

    // Loop through query and set form rows and weights based on field values.
    foreach ($promote_items as $key => $value) {
      $nid = $key;
      $weight = $weight_rows[$nid] ?? 0;

      // Set last weight for first time.
      if (!$form_state->has('last_weight')) {
        $form_state->set('last_weight', $weight);
      }
      $current_last_weight = $form_state->get('last_weight');

      // If current last weight is greater than or equal to last weight, increment it.
      if ($current_last_weight >= $last_weight) {
        $last_weight = $current_last_weight++;
      }
      $form_rows[] = [
        'nid' => $nid,
        'weight' => $weight,
      ];
    }

    // If no rows are set, set default row.
    if (empty($form_rows)) {
      $form_rows[] = [
        'nid' => '',
        'weight' => 0,
      ];
    }

    // Sort table rows by weight.
    uasort($form_rows, [$this, "sortTableRows"]);

    // Reset array indexes.
    $form_rows = array_values($form_rows);

    // Create form.
    $form = $this->generateDraggableTable($form_rows, $form_state);

    // Check to see the num of items in form.
    if (!$form_state->has('num_rows')) {
      $form_state->set('num_rows', count($form_rows));
    }

    // Get num of items and set form display for fields.
    $num_rows = $form_state->get('num_rows');

    // Set actions for form submit.
    $form['dragtablerow_tableset']['actions'] = [
      '#type' => 'actions',
    ];

    // Add more item button.
    $form['dragtablerow_tableset']['actions']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#submit' => [[$this, 'addOne']],
      '#attributes' => [
        'class' => ['use-ajax'],
      ],
      '#ajax' => [
        'callback' => [$this, 'addmoreCallback'],
        'wrapper' => 'drag-table-row-link',
      ],
      '#limit_validation_errors' => [],
    ];

    // Remove item when items have been added.
    if ($num_rows > 1) {
      $form['dragtablerow_tableset']['actions']['remove_item'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => [[$this, 'removeCallback']],
        '#attributes' => [
          'class' => ['use-ajax'],
        ],
        '#ajax' => [
          'callback' => [$this, 'addmoreCallback'],
          'wrapper' => 'drag-table-row-link',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    // Add submit button.
    $form['dragtablerow_tableset']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Check if any content types have the field_promote_weight field.
    $enabled_content_types = $this->fieldHelper->getEnabledContentTypes();
    if (empty($enabled_content_types)) {
      $this->messenger()->addWarning($this->t('No content types have the field_promote_weight field. Please add this field to content types you want to promote. <a href="@url">Manage fields</a>.', [
        '@url' => '/admin/structure/types',
      ]));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Check if any content types have the field enabled.
    $enabled_content_types = $this->fieldHelper->getEnabledContentTypes();
    if (empty($enabled_content_types)) {
      $form_state->setErrorByName('table-row', $this->t('No content types have a promotion weight field. Please enable weighted promotion on content types in their settings.'));
    }

    $rows = $form_state->getValues();

    foreach ($rows as $key => $row) {
      if ($key == 'table-row') {
        $row_count = 0;
        foreach ($row as $key => $entity) {
          $nid = $entity['nid'];
          $article_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($nid);
          if ($article_id === NULL && !empty($nid)) {
            $table_row = 'table-row][' . $row_count;
            $form_state->setErrorByName($table_row, $this->t('Please select a valid content item.'));
          }
          $row_count++;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $current_nodes = [];

    // Retrieve current promoted content.
    $promoted_content = $this->promoteWeightService->getWeightedEntities();
    if (isset($promoted_content)) {
      foreach ($promoted_content as $key => $value) {
        $current_nodes[$key] = $key;
      }
    }

    if (count($values) > 0) {
      // Loop through table rows and set nid and weight.
      foreach ($values as $key => $row) {
        if ($key === "table-row") {
          foreach ($row as $entity) {
            $nid = $entity['nid'];
            $promoted = $entity['promoted'];
            if ($nid !== "" && $promoted == TRUE) {
              $article_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($entity['nid']);
              if (!$article_id) {
                continue;
              }

              // Load the node.
              $node = $this->entityTypeManager->getStorage('node')->load($article_id);
              if (!$node) {
                continue;
              }

              // Get the weight field name dynamically.
              $field_name = $this->fieldHelper->getWeightFieldName($node->bundle());
              if (!$field_name || !$node->hasField($field_name)) {
                $this->messenger()->addWarning($this->t('Node @title (@nid) does not have a promotion weight field and cannot be promoted.', [
                  '@title' => $node->getTitle(),
                  '@nid' => $article_id,
                ]));
                continue;
              }

              // Set promote field to true and weight.
              $node->set('promote', 1);
              $node->set($field_name, $entity['weight']);
              $node->save();

              // When entity key is found in current nodes array unset it.
              if ($article_id !== "" && isset($current_nodes[$article_id])) {
                unset($current_nodes[$article_id]);
              }
            }
          }
        }
      }
    }

    // Loop through current nodes and update promote field to false.
    foreach ($current_nodes as $key => $value) {
      if ($value !== NULL) {
        $node = $this->entityTypeManager->getStorage('node')->load($value);
        if ($node) {
          $field_name = $this->fieldHelper->getWeightFieldName($node->bundle());
          if ($field_name && $node->hasField($field_name)) {
            $node->set($field_name, NULL);
          }
          $node->set('promote', 0);
          $node->save();
        }
      }
    }

    $this->messenger()->addStatus($this->t('The promoted content has been updated.'));
  }

  /**
   * Generate draggable table.
   *
   * @param array $rows
   *   The rows to display.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function generateDraggableTable(array $rows, FormStateInterface $form_state): array {

    $form['info'] = [
      '#type' => 'item',
      '#markup' => $this->t('<h3>Promote Articles</h3>'),
    ];

    // Create tableset wrapper.
    $form['dragtablerow_tableset'] = [
      '#type' => 'fieldset',
      '#title' => 'Drag and Drop to Sort order or remove content article by deleting text in field.',
      '#prefix' => '<div id="drag-table-row-link">',
      '#suffix' => '</div>',
    ];

    // Create first row for table header.
    $form['dragtablerow_tableset']['table-row'] = [
      '#type' => 'table',
      '#empty' => 'No content items available.',
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically
      // prepended; if there is none, an HTML ID is auto-generated.
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];

    // Check to see the num of items in form.
    if (!$form_state->has('num_rows')) {
      $form_state->set('num_rows', count($rows));
    }

    // Get num of items and set form display for fields.
    $num_rows = $form_state->get('num_rows');

    // To keep track of top weight.
    $topWeight = 0;

    for ($i = 0; $i < $num_rows; $i++) {
      // On last loop.
      if ($i == $num_rows - 1) {
        // Set last variable for first time.
        if (!$form_state->has('last_weight')) {
          $form_state->set('last_weight', $rows[$i]['weight']);
        }

        // Get the last weight for add more when ''.
        $topWeight = $form_state->get('last_weight');
      }

      // Set values for link rows.
      $title = $rows[$i]['nid'] ?: '';
      $weight = $rows[$i]['weight'] ?: ($topWeight);

      $form['dragtablerow_tableset']['table-row'][$i] = $this->generateRow($i, $title, $weight);
    }

    return $form;
  }

  /**
   * Generate a single row for the draggable table.
   *
   * @param int $index
   *   The row index.
   * @param int|string $nid
   *   The node ID (can be empty string for new rows).
   * @param int|null $weight
   *   The weight value.
   *
   * @return array
   *   The row form element.
   */
  protected function generateRow(int $index, int|string $nid, ?int $weight = NULL): array {

    $row['name'] = [
      '#markup' => '',
    ];

    // Add draggable class for row.
    $row['#attributes']['class'][] = 'draggable';

    // TableDrag: Sort the table row according to its existing/configured weight.
    // Set the weight for current row.
    $row['#weight'] = $weight;
    $node = $nid ? $this->entityTypeManager->getStorage('node')->load($nid) : NULL;
    $default_value = NULL;
    $type = NULL;
    $title = NULL;

    if ($node !== NULL) {
      $nid = $node->id();
      $type = $node->getType();
      $title = $node->getTitle();
      $default_value = sprintf('%s (%d)', $title, $nid);

      // Get the node bundle name not the machine name.
      $node_type_entity = $this->entityTypeManager
        ->getStorage('node_type')
        ->load($type);
      $type = $node_type_entity ? $node_type_entity->label() : $type;
    }

    // Second row for link fields.
    $row['nid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content'),
      '#autocomplete_route_name' => 'promote_it.autocomplete',
      '#default_value' => $default_value,
    ];

    // Default to the node's current promote status; FALSE for new rows.
    $promoted = $node ? (bool) $node->get('promote')->value : FALSE;

    // Create a row that is a checkbox called promoted.
    $row['promoted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Promoted'),
      '#default_value' => $promoted,
    ];

    // Create a row that is html markup for the node title.
    $row['type'] = [
      '#markup' => $type,
    ];

    // Third row for weight drop down option.
    $row['weight'] = [
      '#type' => 'weight',
      '#title' => 'Weight for ' . $title,
      '#title_display' => 'invisible',
      '#default_value' => $weight,
      // Classify the weight element for #tabledrag set in Table head.
      '#attributes' => ['class' => ['table-sort-weight']],
    ];

    return $row;
  }

  /**
   * Add one callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addOne(array &$form, FormStateInterface $form_state): void {
    $name_field = $form_state->get('num_rows');
    $add_button = $name_field + 1;
    $form_state->set('num_rows', $add_button);

    // Get the next available weight.
    $last_weight = $this->promoteWeightService->getNextWeight();
    $form_state->set('last_weight', $last_weight);

    $form_state->setRebuild();
  }

  /**
   * Add more callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   The form element.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state): mixed {
    // The form passed here is the entire form, not the subform that is
    // passed to non-AJAX callback.
    return $form['dragtablerow_tableset'];
  }

  /**
   * Remove callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state): void {
    $name_field = $form_state->get('num_rows');

    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_rows', $remove_button);
    }

    $last_weight = $form_state->get('last_weight');
    $last_weight = $last_weight - 1;
    $form_state->set('last_weight', $last_weight);

    $form_state->setRebuild();
  }

  /**
   * Call back function to better sort weights on display.
   */
  private function sortTableRows($a, $b): int {
    if (isset($a['weight']) && isset($b['weight'])) {
      return $a['weight'] < $b['weight'] ? -1 : 1;
    }

    return 0;
  }

}
