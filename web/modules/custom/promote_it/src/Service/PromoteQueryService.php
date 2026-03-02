<?php

declare(strict_types=1);

namespace Drupal\promote_it\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service to query and retrieve promoted content nodes.
 *
 * This service provides methods to retrieve promoted content that has been
 * configured with promote_weight fields, sorted by their weight values.
 *
 * @see \Drupal\promote_it\Plugin\Field\FieldType\PromoteWeightItem
 * @see \Drupal\promote_it\Service\PromoteFieldHelperService
 */
final class PromoteQueryService {

  /**
   * Constructs a new PromoteQueryService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\promote_it\Service\PromoteFieldHelperService $fieldHelper
   *   The field helper service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly PromoteFieldHelperService $fieldHelper,
  ) {}

  /**
   * Get promoted content nodes sorted by weight.
   *
   * @return array
   *   An array of promoted node entities sorted by promotion weight.
   *   Lower weights appear first.
   */
  public function getPromoted(): array {
    // Get content types that have the promote_weight field.
    $content_types = $this->fieldHelper->getEnabledContentTypes();

    if (empty($content_types)) {
      return [];
    }

    // Query for each content type.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('type', $content_types, 'IN');
    $query->condition('status', 1);
    $query->condition('promote', 1);
    $entity_ids = $query->execute();

    // Get all nodes for the content types.
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($entity_ids);

    // Filter to only include nodes that actually have the field and sort by weight.
    $nodes_with_field = [];
    foreach ($nodes as $node) {
      $field_name = $this->fieldHelper->getWeightFieldName($node->bundle());
      if ($field_name && $node->hasField($field_name)) {
        $nodes_with_field[] = $node;
      }
    }

    // Sort by weight. Cache field names per bundle to avoid repeated lookups.
    $field_name_cache = [];
    usort($nodes_with_field, function ($a, $b) use (&$field_name_cache) {
      $bundle_a = $a->bundle();
      $bundle_b = $b->bundle();
      if (!isset($field_name_cache[$bundle_a])) {
        $field_name_cache[$bundle_a] = $this->fieldHelper->getWeightFieldName($bundle_a);
      }
      if (!isset($field_name_cache[$bundle_b])) {
        $field_name_cache[$bundle_b] = $this->fieldHelper->getWeightFieldName($bundle_b);
      }
      $weight_a = $field_name_cache[$bundle_a] ? ($a->get($field_name_cache[$bundle_a])->value ?? 0) : 0;
      $weight_b = $field_name_cache[$bundle_b] ? ($b->get($field_name_cache[$bundle_b])->value ?? 0) : 0;
      return $weight_a <=> $weight_b;
    });

    return $nodes_with_field;
  }

}
