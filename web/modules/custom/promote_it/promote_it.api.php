<?php

/**
 * @file
 * Hooks provided by the Promote It module.
 *
 * This file documents all hooks that the Promote It module invokes or that
 * other modules may implement to interact with Promote It functionality.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the Views data definitions exposed by the Promote It module.
 *
 * The Promote It module adds Views handlers for the promotion weight field
 * so that Views queries can sort and filter by promotion weight. Implement
 * this hook to modify or extend those definitions.
 *
 * @param array $data
 *   The Views data array, passed by reference. Keyed by table name.
 *
 * @see promote_it_views_data_alter()
 * @see \Drupal\views\ViewsData
 */
function hook_promote_it_views_data_alter(array &$data): void {
  // Example: change the sort handler for the promotion weight field.
  if (isset($data['node__field_promotion_weight']['field_promotion_weight_value']['sort'])) {
    $data['node__field_promotion_weight']['field_promotion_weight_value']['sort']['id'] = 'standard';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
