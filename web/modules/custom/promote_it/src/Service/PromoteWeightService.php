<?php

declare(strict_types=1);

namespace Drupal\promote_it\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service to handle weight operations for promoted nodes.
 *
 * Provides CRUD operations for managing promotion weight values on nodes
 * with promote_weight fields.
 *
 * @see \Drupal\promote_it\Plugin\Field\FieldType\PromoteWeightItem
 * @see \Drupal\promote_it\Service\PromoteFieldHelperService
 */
final class PromoteWeightService {

  /**
   * Constructs a new PromoteWeightService object.
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
   * Get the weighted order of entities.
   *
   * Retrieves all promoted nodes and returns
   * their IDs with current weight values.
   *
   * @return array
   *   An associative array of entity IDs and their weights.
   */
  public function getWeightedEntities(): array {
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Query all promoted nodes.
    $query = $node_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->condition('promote', 1);

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    $nodes = $node_storage->loadMultiple($nids);
    $entities = [];

    foreach ($nodes as $node) {
      $field_name = $this->fieldHelper->getWeightFieldName($node->bundle());
      if ($field_name && $node->hasField($field_name)) {
        $weight = $node->get($field_name)->value ?? 0;
        $entities[$node->id()] = (int) $weight;
      }
    }

    return $entities;
  }

  /**
   * Save the weighted order of entities.
   *
   * Updates the promote_weight field value for the specified nodes.
   *
   * @param array $entities
   *   An array of entities with 'nid' and 'weight' keys.
   */
  public function saveWeightedEntities(array $entities): void {
    $node_storage = $this->entityTypeManager->getStorage('node');

    foreach ($entities as $entity) {
      $entity_id = $entity['nid'];
      $weight = $entity['weight'];

      $node = $node_storage->load($entity_id);
      if ($node) {
        $field_name = $this->fieldHelper->getWeightFieldName($node->bundle());
        if ($field_name && $node->hasField($field_name)) {
          $node->set($field_name, $weight);
          $node->save();
        }
      }
    }
  }

  /**
   * Get the next available weight (highest current weight + 1).
   *
   * Useful for adding new promoted content with an appropriate weight value.
   *
   * @return int
   *   The next available weight value. Returns 0 if no promoted content exists.
   */
  public function getNextWeight(): int {
    $weighted_entities = $this->getWeightedEntities();

    if (empty($weighted_entities)) {
      return 0;
    }

    $max_weight = max($weighted_entities);
    return $max_weight + 1;
  }

}
