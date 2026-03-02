<?php

declare(strict_types=1);

namespace Drupal\promote_it\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'promote_weight' field type.
 *
 * @FieldType(
 *   id = "promote_weight",
 *   label = @Translation("Promotion Weight"),
 *   description = @Translation("Stores the weight for promoted content ordering."),
 *   category = @Translation("Number"),
 *   default_widget = "promote_weight",
 *   default_formatter = "number_integer",
 *   column_groups = {
 *     "value" = {
 *       "label" = @Translation("Weight"),
 *       "columns" = {
 *         "value"
 *       }
 *     }
 *   }
 * )
 */
final class PromoteWeightItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Weight value'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $values['value'] = rand(-100, 100);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
