<?php

declare(strict_types=1);

namespace Drupal\promote_it\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\promote_it\Service\PromoteQueryService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Promoted Content' block.
 *
 * Renders promoted content ordered by promotion weight without requiring a
 * Views configuration. Simply place this block in any region and configure
 * the number of items and view mode; no additional setup is required.
 *
 * @Block(
 *   id = "promote_it_promoted_content",
 *   admin_label = @Translation("Promoted Content"),
 *   category = @Translation("Content"),
 * )
 */
class PromotedContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The promote query service.
   *
   * @var \Drupal\promote_it\Service\PromoteQueryService
   */
  protected PromoteQueryService $promoteQueryService;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * Constructs a new PromotedContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\promote_it\Service\PromoteQueryService $promoteQueryService
   *   The promote query service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    PromoteQueryService $promoteQueryService,
    EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->promoteQueryService = $promoteQueryService;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('promote_it.promote_query_service'),
      $container->get('entity_display.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'count' => 10,
      'view_mode' => 'teaser',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    // Build view mode options from all view modes registered for nodes.
    $view_modes = $this->entityDisplayRepository->getViewModes('node');
    // Always include 'full' and 'teaser' as baseline options.
    $view_mode_options = [
      'full' => $this->t('Full content'),
      'teaser' => $this->t('Teaser'),
    ];
    foreach ($view_modes as $id => $info) {
      $view_mode_options[$id] = $info['label'];
    }

    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items'),
      '#description' => $this->t('Maximum number of promoted items to display. Enter 0 for no limit.'),
      '#default_value' => $this->configuration['count'],
      '#min' => 0,
      '#max' => 100,
      '#required' => TRUE,
    ];

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#description' => $this->t('The view mode used to render each promoted item.'),
      '#options' => $view_mode_options,
      '#default_value' => $this->configuration['view_mode'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['count'] = (int) $form_state->getValue('count');
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $nodes = $this->promoteQueryService->getPromoted();

    // Apply the configured item limit.
    $count = (int) $this->configuration['count'];
    if ($count > 0) {
      $nodes = array_slice($nodes, 0, $count);
    }

    // The node_list cache tag ensures the block is invalidated whenever any
    // node is saved, keeping the ordered list always current.
    $build = [
      '#cache' => [
        'tags' => ['node_list'],
      ],
    ];

    if (empty($nodes)) {
      return $build;
    }

    $view_mode = $this->configuration['view_mode'] ?? 'teaser';
    $view_builder = $this->entityTypeManager->getViewBuilder('node');

    // Render each node individually to preserve the weighted sort order.
    // (viewMultiple rekeys by entity ID which would lose the ordering.)
    foreach ($nodes as $node) {
      $build[] = $view_builder->view($node, $view_mode);
    }

    return $build;
  }

}
