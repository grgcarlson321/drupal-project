<?php

declare(strict_types=1);

namespace Drupal\promote_it\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'promote_weight' field widget.
 *
 * @FieldWidget(
 *   id = "promote_weight",
 *   label = @Translation("Promotion weight"),
 *   field_types = {"promote_weight"}
 * )
 */
final class PromoteWeightWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['value'] = [
      '#type' => 'number',
      '#default_value' => $items[$delta]->value ?? 0,
      '#min' => -100,
      '#max' => 100,
      '#step' => 1,
      '#description' => $this->t('Lower values appear first in promoted content lists.'),
      '#title' => $this->t('Weight'),
      '#title_display' => 'invisible',
    ];

    return $element;
  }

}
