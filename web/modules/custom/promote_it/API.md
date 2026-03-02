# Promote It API Documentation

This document provides comprehensive API documentation for developers who want to integrate the Promote It module into custom code, themes, or modules.

## Table of Contents

1. [Overview](#overview)
2. [Services](#services)
3. [Field Type API](#field-type-api)
4. [Programmatic Usage](#programmatic-usage)
5. [Hooks](#hooks)
6. [Views Integration](#views-integration)
7. [Events and Alter Hooks](#events-and-alter-hooks)
8. [Code Examples](#code-examples)
9. [Best Practices](#best-practices)
10. [Testing](#testing)

---

## Overview

The Promote It module provides three main integration points for developers:

1. **Services**: Programmatic access to promoted content and weight management
2. **Field Type**: Custom `promote_weight` field type for storing weight values
3. **Hooks**: Integration points for altering behavior

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Promote It Module                        │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌─────────────────────┐  ┌─────────────────────────────┐  │
│  │   Field Type API    │  │      Service Layer           │  │
│  ├─────────────────────┤  ├─────────────────────────────┤  │
│  │ PromoteWeightItem   │  │ PromoteFieldHelperService   │  │
│  │ PromoteWeightWidget │  │ PromoteQueryService         │  │
│  └─────────────────────┘  │ PromoteWeightService        │  │
│                            └─────────────────────────────┘  │
│                                                               │
│  ┌─────────────────────────────────────────────────────────┐│
│  │                    Integration Layer                     ││
│  ├─────────────────────────────────────────────────────────┤│
│  │  • Views integration (hook_views_data_alter)            ││
│  │  • Node form alter (hook_form_node_form_alter)          ││
│  │  • Content type settings (hook_form_node_type_form...)  ││
│  └─────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
```

---

## Services

All services use dependency injection and follow Drupal best practices.

### PromoteFieldHelperService

**Service ID:** `promote_it.field_helper`

Helper service for working with promote_weight fields across content types.

#### Methods

##### getEnabledContentTypes()

Get all content types that have a promote_weight field attached.

```php
public function getEnabledContentTypes(): array
```

**Returns:** Array of content type machine names

**Example:**
```php
$field_helper = \Drupal::service('promote_it.field_helper');
$content_types = $field_helper->getEnabledContentTypes();
// Returns: ['article', 'page', 'event']
```

##### getWeightFieldName()

Get the machine name of the promote_weight field for a specific bundle.

```php
public function getWeightFieldName(string $bundle): ?string
```

**Parameters:**
- `$bundle` - The content type machine name (e.g., 'article')

**Returns:** Field name string or NULL if not found

**Example:**
```php
$field_helper = \Drupal::service('promote_it.field_helper');
$field_name = $field_helper->getWeightFieldName('article');
// Returns: 'field_promotion_weight'
```

##### hasWeightField()

Check if a bundle has a promote_weight field attached.

```php
public function hasWeightField(string $bundle): bool
```

**Parameters:**
- `$bundle` - The content type machine name

**Returns:** TRUE if field exists, FALSE otherwise

**Example:**
```php
$field_helper = \Drupal::service('promote_it.field_helper');
if ($field_helper->hasWeightField('article')) {
  // Content type supports weighted promotion
}
```

---

### PromoteQueryService

**Service ID:** `promote_it.promote_query_service`

Service for querying and retrieving promoted content sorted by weight.

#### Methods

##### getPromoted()

Get all promoted content sorted by promotion weight (lowest to highest).

```php
public function getPromoted(): array
```

**Returns:** Array of loaded node entities

**Example:**
```php
$query_service = \Drupal::service('promote_it.promote_query_service');
$promoted_nodes = $query_service->getPromoted();

foreach ($promoted_nodes as $node) {
  $title = $node->getTitle();
  $weight = $node->get('field_promotion_weight')->value ?? 0;
  // Process node...
}
```

**Notes:**
- Only returns published (`status = 1`) and promoted (`promote = 1`) content
- Only includes content types with promote_weight fields
- Results are sorted by weight ascending (lowest first)
- Respects node access permissions

---

### PromoteWeightService

**Service ID:** `promote_it.promote_weight_service`

Service for managing weight values on promoted content.

#### Methods

##### getWeightedEntities()

Get all promoted entities with their current weight values.

```php
public function getWeightedEntities(): array
```

**Returns:** Associative array where keys are node IDs and values are weight integers

**Example:**
```php
$weight_service = \Drupal::service('promote_it.promote_weight_service');
$weights = $weight_service->getWeightedEntities();
// Returns: [123 => 0, 456 => 5, 789 => 10]
```

##### saveWeightedEntities()

Save weight values for multiple entities.

```php
public function saveWeightedEntities(array $entities): void
```

**Parameters:**
- `$entities` - Array of entities with 'nid' and 'weight' keys

**Example:**
```php
$weight_service = \Drupal::service('promote_it.promote_weight_service');
$weight_service->saveWeightedEntities([
  ['nid' => 123, 'weight' => -10],
  ['nid' => 456, 'weight' => 0],
  ['nid' => 789, 'weight' => 10],
]);
```

##### getNextWeight()

Get the next available weight value (current max + 1).

```php
public function getNextWeight(): int
```

**Returns:** Next available weight integer

**Example:**
```php
$weight_service = \Drupal::service('promote_it.promote_weight_service');
$next = $weight_service->getNextWeight();
// Returns: 11 (if max current weight is 10)

// Use when promoting new content
$node->set('field_promotion_weight', $next);
```

---

## Field Type API

### PromoteWeightItem

**Field Type ID:** `promote_weight`

Custom field type for storing promotion weight values.

#### Field Storage Schema

```php
[
  'columns' => [
    'value' => [
      'type' => 'int',
      'size' => 'tiny',  // -128 to 127 range
      'not null' => FALSE,
    ],
  ],
]
```

#### Using the Field Type

**Add field via UI:**
1. Go to Structure → Content types → [Type] → Manage fields
2. Click "Add field"
3. Select "Promotion weight" from field type dropdown
4. Configure and save

**Add field programmatically:**
```php
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

// Create field storage (if not exists)
if (!FieldStorageConfig::loadByName('node', 'field_promotion_weight')) {
  FieldStorageConfig::create([
    'field_name' => 'field_promotion_weight',
    'entity_type' => 'node',
    'type' => 'promote_weight',
    'cardinality' => 1,
  ])->save();
}

// Add field to bundle
FieldConfig::create([
  'field_name' => 'field_promotion_weight',
  'entity_type' => 'node',
  'bundle' => 'article',
  'label' => 'Promotion Weight',
  'required' => FALSE,
])->save();
```

#### Field Widget

**Widget ID:** `promote_weight`

The widget provides a number input with these constraints:
- **Type:** Integer
- **Range:** -100 to 100
- **Step:** 1
- **Default:** 0 (when empty)

---

## Programmatic Usage

### Promoting Content with Weight

```php
use Drupal\node\Entity\Node;

// Load or create a node
$node = Node::load($nid);

// Promote the node and set weight
$node->set('promote', 1);
$node->set('field_promotion_weight', -10);  // High priority
$node->save();
```

### Unpromoting Content

```php
$node = Node::load($nid);
$node->set('promote', 0);
// Optional: Keep weight value for when it's promoted again
$node->save();
```

### Batch Update Weights

```php
$weight_service = \Drupal::service('promote_it.promote_weight_service');

$updates = [
  ['nid' => 101, 'weight' => -50],  // Breaking news
  ['nid' => 102, 'weight' => -10],  // Featured article
  ['nid' => 103, 'weight' => 0],    // Regular promoted
  ['nid' => 104, 'weight' => 10],   // Lower priority
];

$weight_service->saveWeightedEntities($updates);
```

### Query Promoted Content with Custom Criteria

```php
$query = \Drupal::entityQuery('node')
  ->accessCheck(TRUE)
  ->condition('status', 1)
  ->condition('promote', 1)
  ->condition('type', 'article')
  ->condition('field_promotion_weight', 0, '<')  // Only negative weights
  ->sort('field_promotion_weight', 'ASC')
  ->range(0, 5);

$nids = $query->execute();
$nodes = Node::loadMultiple($nids);
```

### Custom Service Integration

```php
namespace Drupal\my_module\Service;

use Drupal\promote_it\Service\PromoteQueryService;
use Drupal\promote_it\Service\PromoteFieldHelperService;

class MyCustomService {

  public function __construct(
    private readonly PromoteQueryService $promoteQuery,
    private readonly PromoteFieldHelperService $fieldHelper,
  ) {}

  public function getTopPromoted(int $limit = 5): array {
    $all_promoted = $this->promoteQuery->getPromoted();
    return array_slice($all_promoted, 0, $limit);
  }

  public function getPromotedByType(string $type): array {
    if (!$this->fieldHelper->hasWeightField($type)) {
      return [];
    }

    $all_promoted = $this->promoteQuery->getPromoted();
    return array_filter($all_promoted, fn($node) => $node->bundle() === $type);
  }
}
```

---

## Hooks

### hook_form_node_type_form_alter()

The module implements this hook to add the "Enable promotion weight field" checkbox to content type settings forms.

**Custom Extension Example:**
```php
/**
 * Implements hook_form_FORM_ID_alter().
 */
function my_module_form_node_type_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // React to Promote It's checkbox
  $form['promote_it']['my_custom_setting'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable custom promotion feature'),
    '#states' => [
      'visible' => [
        ':input[name="enable_promotion_weight"]' => ['checked' => TRUE],
      ],
    ],
  ];
}
```

### hook_form_node_form_alter()

The module uses this to place the weight field in the "Promotion options" section.

**Custom Extension Example:**
```php
/**
 * Implements hook_form_alter().
 */
function my_module_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (strpos($form_id, '_node_form') !== FALSE) {
    $node = $form_state->getFormObject()->getEntity();
    
    // Check if node type has weight field
    $field_helper = \Drupal::service('promote_it.field_helper');
    if ($field_helper->hasWeightField($node->bundle())) {
      // Add custom validation
      $form['#validate'][] = 'my_module_weight_validate';
    }
  }
}

function my_module_weight_validate($form, FormStateInterface $form_state) {
  $weight = $form_state->getValue(['field_promotion_weight', 0, 'value']);
  if ($weight !== NULL && abs($weight) > 50) {
    $form_state->setErrorByName('field_promotion_weight', 
      t('Weight values above 50 or below -50 are reserved for administrators.'));
  }
}
```

### hook_views_data_alter()

The module implements this to ensure the weight field is available in Views.

**How It Works:**
```php
function promote_it_views_data_alter(array &$data) {
  // Ensures 'field_promotion_weight' appears in Views UI
  // Adds proper sort handlers and filters
}
```

---

## Views Integration

### Using Promotion Weight in Views

The promotion weight field is fully integrated with Views and can be used for:
- **Sorting**: Order by weight (ascending/descending)
- **Filtering**: Filter by weight range
- **Fields**: Display weight value
- **Contextual filters**: Filter by weight argument

### Views API Example

```php
use Drupal\views\Views;

$view = Views::getView('promoted_content');
$view->setDisplay('default');

// Add weight sort
$view->display_handler->overrideOption('sorts', [
  'field_promotion_weight_value' => [
    'id' => 'field_promotion_weight_value',
    'table' => 'node__field_promotion_weight',
    'field' => 'field_promotion_weight_value',
    'relationship' => 'none',
    'order' => 'ASC',
  ],
]);

// Add promote filter
$view->display_handler->overrideOption('filters', [
  'promote' => [
    'id' => 'promote',
    'table' => 'node_field_data',
    'field' => 'promote',
    'value' => '1',
  ],
]);

$view->execute();
$results = $view->result;
```

### Creating a View Programmatically

```php
use Drupal\views\Entity\View;

$view = View::create([
  'id' => 'my_promoted_content',
  'label' => 'My Promoted Content',
  'module' => 'my_module',
  'base_table' => 'node_field_data',
  'display' => [
    'default' => [
      'display_plugin' => 'default',
      'display_options' => [
        'sorts' => [
          'field_promotion_weight_value' => [
            'id' => 'field_promotion_weight_value',
            'field' => 'field_promotion_weight_value',
            'order' => 'ASC',
          ],
        ],
        'filters' => [
          'status' => ['value' => '1'],
          'promote' => ['value' => '1'],
        ],
      ],
    ],
  ],
]);
$view->save();
```

---

## Events and Alter Hooks

### Entity Presave Hook

React to weight changes before saving:

```php
/**
 * Implements hook_entity_presave().
 */
function my_module_entity_presave(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'node' && $entity->hasField('field_promotion_weight')) {
    $old_weight = $entity->original?->get('field_promotion_weight')->value;
    $new_weight = $entity->get('field_promotion_weight')->value;
    
    if ($old_weight !== $new_weight) {
      \Drupal::logger('my_module')->info(
        'Promotion weight changed from @old to @new for @title',
        [
          '@old' => $old_weight ?? 'none',
          '@new' => $new_weight ?? 'none',
          '@title' => $entity->getTitle(),
        ]
      );
    }
  }
}
```

### Altering Promoted Content Query

```php
/**
 * Implements hook_query_alter().
 */
function my_module_query_alter(QueryAlterableInterface $query) {
  if ($query->hasTag('promoted_content')) {
    // Add custom conditions
    $query->condition('created', strtotime('-30 days'), '>');
  }
}

// Tag your query
$query = \Drupal::entityQuery('node')
  ->addTag('promoted_content')
  ->condition('promote', 1);
```

---

## Code Examples

### Example 1: Custom Block Showing Top Promoted

```php
namespace Drupal\my_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\promote_it\Service\PromoteQueryService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Top Promoted Content' Block.
 *
 * @Block(
 *   id = "top_promoted_block",
 *   admin_label = @Translation("Top Promoted Content"),
 * )
 */
class TopPromotedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly PromoteQueryService $promoteQuery,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('promote_it.promote_query_service')
    );
  }

  public function build() {
    $promoted_nodes = $this->promoteQuery->getPromoted();
    $top_five = array_slice($promoted_nodes, 0, 5);

    $items = [];
    foreach ($top_five as $node) {
      $items[] = [
        '#type' => 'link',
        '#title' => $node->getTitle(),
        '#url' => $node->toUrl(),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#cache' => [
        'tags' => ['node_list:promoted'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }
}
```

### Example 2: Drush Command to Reweight Content

```php
namespace Drupal\my_module\Commands;

use Drupal\promote_it\Service\PromoteWeightService;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for managing promoted content.
 */
class PromotedContentCommands extends DrushCommands {

  public function __construct(
    private readonly PromoteWeightService $weightService,
  ) {
    parent::__construct();
  }

  /**
   * Reset all promotion weights to sequential values.
   *
   * @command promote:reweight
   * @aliases prew
   */
  public function reweight() {
    $weights = $this->weightService->getWeightedEntities();
    asort($weights);
    
    $updates = [];
    $new_weight = 0;
    foreach (array_keys($weights) as $nid) {
      $updates[] = ['nid' => $nid, 'weight' => $new_weight];
      $new_weight += 5;
    }
    
    $this->weightService->saveWeightedEntities($updates);
    $this->output()->writeln('Reweighted ' . count($updates) . ' promoted nodes.');
  }
}
```

### Example 3: REST Resource for Promoted Content

```php
namespace Drupal\my_module\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\promote_it\Service\PromoteQueryService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Promoted Content Resource.
 *
 * @RestResource(
 *   id = "promoted_content",
 *   label = @Translation("Promoted Content"),
 *   uri_paths = {
 *     "canonical" = "/api/promoted-content"
 *   }
 * )
 */
class PromotedContentResource extends ResourceBase {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    $logger,
    private readonly PromoteQueryService $promoteQuery,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('promote_it.promote_query_service')
    );
  }

  public function get() {
    $promoted = $this->promoteQuery->getPromoted();
    
    $data = array_map(function($node) {
      return [
        'nid' => $node->id(),
        'title' => $node->getTitle(),
        'weight' => $node->get('field_promotion_weight')->value ?? 0,
        'url' => $node->toUrl()->setAbsolute()->toString(),
      ];
    }, $promoted);

    return new ResourceResponse($data);
  }
}
```

---

## Best Practices

### Dependency Injection

Always use dependency injection for services:

```php
// ✅ GOOD
class MyClass {
  public function __construct(
    private readonly PromoteQueryService $promoteQuery,
  ) {}
}

// ❌ BAD
$service = \Drupal::service('promote_it.promote_query_service');
```

### Caching

Always add appropriate cache metadata:

```php
$build = [
  '#markup' => $content,
  '#cache' => [
    'tags' => ['node_list:promoted'],  // Clear when promoted nodes change
    'contexts' => ['user.permissions'], // Vary by permissions
    'max-age' => 3600,                  // Cache for 1 hour
  ],
];
```

### Access Checking

Always respect entity access:

```php
// ✅ GOOD - Respects access
$query = \Drupal::entityQuery('node')
  ->accessCheck(TRUE)
  ->condition('promote', 1);

// ❌ BAD - Bypasses access
$query = \Drupal::entityQuery('node')
  ->accessCheck(FALSE)
  ->condition('promote', 1);
```

### Error Handling

Handle missing fields gracefully:

```php
$field_helper = \Drupal::service('promote_it.field_helper');

if (!$field_helper->hasWeightField($bundle)) {
  \Drupal::messenger()->addWarning(
    t('The @type content type does not support weighted promotion.', [
      '@type' => $bundle,
    ])
  );
  return;
}
```

---

## Testing

### Unit Test Example

```php
namespace Drupal\Tests\my_module\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\promote_it\Service\PromoteFieldHelperService;

class MyServiceTest extends UnitTestCase {

  public function testPromotedContentLogic() {
    $field_helper = $this->createMock(PromoteFieldHelperService::class);
    $field_helper->method('getEnabledContentTypes')
      ->willReturn(['article', 'page']);

    $service = new MyService($field_helper);
    $result = $service->someMethod();
    
    $this->assertEquals(['article', 'page'], $result);
  }
}
```

### Kernel Test Example

```php
namespace Drupal\Tests\my_module\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

class PromotedContentIntegrationTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'node', 'field', 'promote_it'];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['promote_it']);

    NodeType::create(['type' => 'article'])->save();
  }

  public function testWeightService() {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test',
      'promote' => 1,
      'field_promotion_weight' => 10,
    ]);
    $node->save();

    $weight_service = \Drupal::service('promote_it.promote_weight_service');
    $weights = $weight_service->getWeightedEntities();

    $this->assertArrayHasKey($node->id(), $weights);
    $this->assertEquals(10, $weights[$node->id()]);
  }
}
```

---

## Additional Resources

- **Module README**: [README.md](README.md)
- **User Guide**: [USER_GUIDE.md](USER_GUIDE.md)
- **Drupal.org Project Page**: [Link when available]
- **Issue Queue**: [Link when available]

---

*Last Updated: February 2026*
