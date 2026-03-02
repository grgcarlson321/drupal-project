# Promote It Module

The Promote It module provides a custom field type and drag-and-drop interface for managing the order of promoted content on your Drupal site. It allows you to set the weight/order of promoted nodes, which can then be displayed in custom blocks or views.

Perfect for managing front page content, featured articles, or any ordered list of promoted content without relying on configuration-based ordering.

## Features

- **Custom Field Type**: Provides a `promote_weight` field type that can be attached to any content type
- **Automatic Field Management**: Fields are automatically added/removed via content type settings
- **Drag-and-Drop Interface**: Easy-to-use interface at `/admin/content/promote` for managing promoted content order
- **Smart Weight Assignment**: Newly promoted content automatically gets assigned the next available weight
- **Flexible Integration**: Works with any content type that has the promotion weight field enabled
- **Views Integration**: Weight field is available in Views for sorting and filtering
- **Permission-Based Access**: Granular permissions control who can manage promoted content order
- **Editor-Friendly**: Editors can manually set weights on the node edit form if needed
- **Production-Ready**: Thoroughly tested with Unit, Kernel, and Functional tests

## Requirements

- Drupal 10.3+ or Drupal 11+
- PHP 8.1+
- Node module (core)
- Field module (core)

## Installation

1. Enable the module:
   ```bash
   drush en promote_it -y
   ```

2. The module will automatically create the field storage for the promotion weight field.

3. Assign permissions as needed at `/admin/people/permissions`:
   - **Manage promoted content order**: Access to the `/admin/content/promote` interface

## Configuration

### Enabling Weighted Promotion on Content Types

1. Go to **Structure** > **Content types** (`/admin/structure/types`)
2. Select a content type (e.g., Article)
3. Click **Edit**
4. Look for the **Promote It** section (usually near Publishing options)
5. Check **"Enable promotion weight field"**
6. Click **Save content type**

The promotion weight field will be automatically added to the content type and will appear in the "Promotion options" section on node edit forms.

### Removing Weighted Promotion

To disable weighted promotion for a content type:

1. Go to **Structure** > **Content types**
2. Select the content type
3. Click **Edit**
4. In the **Promote It** section, uncheck **"Enable promotion weight field"**
5. Click **Save content type**

The field will be automatically removed from that content type.

## Usage

### Managing Promoted Content Order

1. Navigate to **Content** > **Promote it** (`/admin/content/promote`)
2. **Add Content**: Use the autocomplete field to search for and add content items
3. **Promote/Unpromote**: Check or uncheck the "Promoted" checkbox for each item
4. **Reorder**: Drag and drop rows to reorder the promoted content
5. **Save**: Click **Save** to apply your changes

All items are sorted by weight (lowest to highest), so content with lower weights appears first.

### Manual Weight Assignment

Editors can also set weights directly on the node edit form:

1. Edit any node of a content type with weighted promotion enabled
2. Expand the **Promotion options** section
3. Enter a weight value (-100 to 100) in the **Promotion weight** field
   - Lower weights (e.g., -50) appear first
   - Higher weights (e.g., 50) appear last
   - Default weight is 0
4. Save the node

### Using in Views

1. Create or edit a View
2. Add a **Sort Criteria**
3. Search for "Promotion weight" field
4. Choose ascending (low to high) or descending (high to low)
5. Optionally add a **Filter Criteria** to show only promoted content (`Content: Promoted = Yes`)

## Technical Details

### Field Type

The module provides a custom `promote_weight` field type that:
- Stores integer values from -100 to 100
- Is nullable (nodes without a weight value default to 0)
- Uses shared field storage named `field_promotion_weight`
- Is automatically detected by field type (`promote_weight`), not field machine name
- Can be attached to multiple content types simultaneously

### Services Architecture

The module provides a clean service architecture for programmatic access:

#### promote_it.field_helper
Helper service for identifying content types with promotion weight fields.

**Methods:**
- `getEnabledContentTypes()`: Returns array of content type machine names that have promote_weight fields
- `getWeightFieldName(string $bundle)`: Returns the field name for a given bundle, or NULL
- `hasWeightField(string $bundle)`: Checks if a bundle has a weight field

#### promote_it.promote_query_service
Service for querying and retrieving promoted content sorted by weight.

**Methods:**
- `getPromoted()`: Returns an array of promoted node entities sorted by weight (lowest first)

#### promote_it.promote_weight_service
Service for CRUD operations on weight values.

**Methods:**
- `getWeightedEntities()`: Returns associative array of entity IDs and their weights
- `saveWeightedEntities(array $entities)`: Saves weight values for multiple entities
- `getNextWeight()`: Returns the next available weight value (max + 1)

### API Usage Examples

#### Get Promoted Content for Display

```php
// Inject the service in your class constructor
public function __construct(
  private readonly PromoteQueryService $promoteQueryService,
) {}

// In your class, get promoted nodes
$promoted_nodes = $this->promoteQueryService->getPromoted();

// Iterate through nodes
foreach ($promoted_nodes as $node) {
  $title = $node->getTitle();
  $weight = $node->get('field_promotion_weight')->value ?? 0;
  // Render or process node...
}
```

#### Check If Content Type Has Weight Field

```php
// Inject the field helper service
public function __construct(
  private readonly PromoteFieldHelperService $fieldHelper,
) {}

// Check if 'article' content type has the field
if ($this->fieldHelper->hasWeightField('article')) {
  // Content type supports weighted promotion
  $field_name = $this->fieldHelper->getWeightFieldName('article');
}
```

#### Programmatically Set Weight

```php
$node = \Drupal\node\Entity\Node::load($nid);
if ($node && $node->hasField('field_promotion_weight')) {
  $node->set('field_promotion_weight', 5);
  $node->set('promote', 1); // Also promote the node
  $node->save();
}
```

#### Get All Weighted Content

```php
$weight_service = \Drupal::service('promote_it.promote_weight_service');
$weights = $weight_service->getWeightedEntities();
// Returns: [123 => 0, 456 => 5, 789 => 10]
```

### Hooks

The module implements the following hooks:

- **hook_form_node_type_form_alter()**: Adds the "Enable promotion weight field" checkbox to content type settings
- **hook_form_node_form_alter()**: Places the promotion weight field in the "Promotion options" section on node edit forms
- **hook_views_data_alter()**: Ensures the weight field is available in Views for sorting

### Permissions

The module defines the following permission:

- **manage promoted content order**: Grants access to the drag-and-drop interface at `/admin/content/promote`

## File Structure

```
promote_it/
├── src/
│   ├── Plugin/
│   │   └── Field/
│   │       ├── FieldType/
│   │       │   └── PromoteWeightItem.php      # Custom field type definition
│   │       └── FieldWidget/
│   │           └── PromoteWeightWidget.php     # Custom widget for editing
│   ├── Service/
│   │   ├── PromoteFieldHelperService.php       # Helper for field operations
│   │   ├── PromoteWeightService.php            # Weight CRUD operations
│   │   └── PromoteQueryService.php             # Query promoted content
│   ├── Form/
│   │   └── PromoteForm.php                     # Drag-and-drop interface
│   └── Controller/
│       └── PromoteController.php               # Autocomplete endpoint
├── tests/
│   └── src/
│       ├── Unit/                                # Unit tests with mocks
│       ├── Kernel/                              # Kernel tests with Drupal
│       └── Functional/                          # Browser-based tests
├── promote_it.info.yml                         # Module metadata
├── promote_it.install                          # Install/uninstall hooks
├── promote_it.module                           # Module hooks and integrations
├── promote_it.permissions.yml                  # Permission definitions
├── promote_it.routing.yml                      # Route definitions
├── promote_it.links.menu.yml                   # Menu links
├── promote_it.links.task.yml                   # Local task links
├── promote_it.services.yml                     # Service definitions
└── README.md                                   # This file
```

## Testing

The module includes comprehensive test coverage:

### Run All Tests
```bash
./vendor/bin/phpunit web/modules/custom/promote_it
```

### Run Specific Test Suites
```bash
# Unit tests
./vendor/bin/phpunit web/modules/custom/promote_it/tests/src/Unit

# Kernel tests
./vendor/bin/phpunit web/modules/custom/promote_it/tests/src/Kernel

# Functional tests
./vendor/bin/phpunit web/modules/custom/promote_it/tests/src/Functional
```

## Uninstallation

When uninstalling the module:

1. The field storage (`field_promotion_weight`) will be deleted
2. All field instances will be removed from all content types
3. All weight data will be permanently lost

**Important**: Export any critical weight orderings before uninstalling!

To uninstall:
```bash
drush pmu promote_it -y
```

## Troubleshooting

### Field Not Appearing on Content Type

If the promotion weight field isn't appearing after enabling it:

1. Verify weighted promotion is enabled in the content type settings
2. Clear all caches: `drush cr`
3. Check field storage exists: `drush field:list | grep promotion_weight`
4. Check entity field definitions: `drush ev "print_r(\Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article'));"`

### Promoted Content Not Sorting Correctly

If promoted content isn't sorting as expected:

1. Verify nodes have weight values set (check the field on the node edit form)
2. Confirm the `promote` checkbox is checked on the nodes
3. Verify the content type has the promotion weight field attached
4. Check that you're using the PromoteQueryService or Views with proper sort
5. Clear all caches: `drush cr`

### Permission Denied on /admin/content/promote

If users can't access the promote interface:

1. Verify the user has the **"manage promoted content order"** permission
2. Clear all caches
3. Check that the route is properly defined: `drush router:rebuild`

### Weight Field Not Available in Views

If the weight field doesn't appear in Views sorting options:

1. Clear Views cache: `drush cc views` or `drush cr`
2. Verify the content type has the field attached
3. Check that the field instance exists in the database
4. Try recreating the View

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Write tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

### Coding Standards

The module follows Drupal coding standards:
- PSR-4 autoloading
- Type hints and return type declarations
- Dependency injection
- Comprehensive docblocks

### Before Submitting

- Run coding standards checks: `phpcs --standard=Drupal,DrupalPractice web/modules/custom/promote_it`
- Run PHPStan analysis: `phpstan analyse web/modules/custom/promote_it`
- Ensure all tests pass
- Update documentation as needed

## Roadmap

Planned features for future releases:

- **Bulk operations**: Quickly promote/unpromote multiple items at once
- **Scheduling**: Schedule promotion start/end dates
- **Multi-site support**: Share promoted content across multiple sites
- **REST API**: Expose promoted content via REST/JSON:API
- **Admin UI improvements**: Enhanced drag-and-drop with keyboard support

## License

This module is licensed under GPL-2.0-or-later.

## Maintainers

Current maintainers:
- [Add maintainer information here]

## Support

For issues, feature requests, or support:
- Issue queue: [Add issue queue URL if on Drupal.org]
- Documentation: This README and the inline code documentation
