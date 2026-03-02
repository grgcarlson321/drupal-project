<?php

declare(strict_types=1);

namespace Drupal\promote_it\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\promote_it\Service\PromoteFieldHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for autocomplete on the promote content form.
 *
 * Provides autocomplete suggestions for unpromoted content that can be
 * added to the promoted content list.
 *
 * @see \Drupal\promote_it\Form\PromoteForm
 */
class PromoteController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The field helper service.
   *
   * @var \Drupal\promote_it\Service\PromoteFieldHelperService
   */
  protected PromoteFieldHelperService $fieldHelper;

  /**
   * Constructs a new PromoteController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\promote_it\Service\PromoteFieldHelperService $fieldHelper
   *   The field helper service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    PromoteFieldHelperService $fieldHelper,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldHelper = $fieldHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('promote_it.field_helper')
    );
  }

  /**
   * Returns response for the autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request): JsonResponse {
    $results = [];
    $input = $request->query->get('q');
    if (!$input) {
      return new JsonResponse($results);
    }
    $input = Xss::filter($input);
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Get content types that have the field_promote_weight field.
    $selected_content_types = $this->fieldHelper->getEnabledContentTypes();

    // If no content types have the field, return empty results.
    if (empty($selected_content_types)) {
      return new JsonResponse($results);
    }

    $query = $nodeStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $selected_content_types, 'IN')
      ->condition('title', $input, 'CONTAINS')
      ->condition('status', 1)
      ->condition('promote', 0)
      ->groupBy('nid')
      ->sort('created', 'DESC');
    $ids = $query->execute();
    $nodes = $ids ? $this->entityTypeManager->getStorage('node')->loadMultiple($ids) : [];
    // Add node type to label.
    foreach ($nodes as $node) {
      $type = $node->getType();
      // Get the node bundle name not the machine name.
      $type = $this->entityTypeManager
        ->getStorage('node_type')
        ->load($type)
        ->label();
      $results[] = [
        'value' => EntityAutocomplete::getEntityLabels([$node]),
        'label' => $node->getTitle() . ' (' . $node->id() . ") [" . $type . ']',
      ];
    }
    return new JsonResponse($results);
  }

}
