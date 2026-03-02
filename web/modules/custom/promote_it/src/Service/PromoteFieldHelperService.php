<?php

declare(strict_types=1);

namespace Drupal\promote_it\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper service for promote_weight field operations.
 *
 * Provides shared functionality for identifying content types with promotion
 * weight fields and retrieving field names. This eliminates code duplication
 * across forms, controllers, and services.
 *
 * @see \Drupal\promote_it\Plugin\Field\FieldType\PromoteWeightItem
 */
final class PromoteFieldHelperService {

  /**
   * Constructs a new PromoteFieldHelperService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * Get content types that have promotion weight field attached.
   *
   * Scans all node types and identifies those with at least one field
   * of type 'promote_weight'.
   *
   * @return array
   *   An array of content type machine names that have promote_weight fields.
   *   Returns empty array if no content types have the field.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEnabledContentTypes(): array {
    $content_types = [];
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    foreach ($node_types as $node_type) {
      $bundle = $node_type->id();
      $field_definitions = $this->entityFieldManager
        ->getFieldDefinitions('node', $bundle);

      // Look for any field of type 'promote_weight'.
      foreach ($field_definitions as $field_definition) {
        if ($field_definition->getType() === 'promote_weight') {
          $content_types[] = $bundle;
          break;
        }
      }
    }

    return $content_types;
  }

  /**
   * Get the weight field name for a given bundle.
   *
   * Finds the first promote_weight field attached to the specified bundle.
   * In most cases, content types will have only one promote_weight field.
   *
   * @param string $bundle
   *   The bundle machine name (e.g., 'article', 'page').
   *
   * @return string|null
   *   The field name if found (e.g., 'field_promotion_weight'),
   *   NULL if the bundle has no promote_weight fields.
   */
  public function getWeightFieldName(string $bundle): ?string {
    $field_definitions = $this->entityFieldManager
      ->getFieldDefinitions('node', $bundle);

    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_definition->getType() === 'promote_weight') {
        return $field_name;
      }
    }

    return NULL;
  }

  /**
   * Check if a bundle has a promotion weight field.
   *
   * Convenience method to check if a content type is configured for
   * weighted promotion.
   *
   * @param string $bundle
   *   The bundle machine name.
   *
   * @return bool
   *   TRUE if the bundle has at least one promote_weight field.
   */
  public function hasWeightField(string $bundle): bool {
    return $this->getWeightFieldName($bundle) !== NULL;
  }

}
