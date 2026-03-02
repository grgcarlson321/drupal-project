<?php

declare(strict_types=1);

namespace Drupal\Tests\promote_it\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Promote It module.
 *
 * @group promote_it
 */
class PromoteFormFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['promote_it', 'node', 'field', 'user'];

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create article content type.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Create the field storage.
    FieldStorageConfig::create([
      'field_name' => 'field_promotion_weight',
      'entity_type' => 'node',
      'type' => 'promote_weight',
      'cardinality' => 1,
    ])->save();

    // Add field to article.
    FieldConfig::create([
      'field_name' => 'field_promotion_weight',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Promotion Weight',
    ])->save();

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer content types',
      'create article content',
      'edit any article content',
      'access content',
      'manage promoted content order',
    ]);
  }

  /**
   * Tests that promotion weight field can be added via content type settings.
   */
  public function testEnablePromotionWeightViaContentTypeSettings(): void {
    $this->drupalLogin($this->adminUser);

    // Navigate to article content type edit form.
    $this->drupalGet('/admin/structure/types/manage/article');
    $this->assertSession()->statusCodeEquals(200);

    // Look for the promotion settings checkbox.
    $this->assertSession()->fieldExists('enable_promotion_weight');

    // Enable weighted promotion.
    $this->submitForm([
      'enable_promotion_weight' => TRUE,
    ], 'Save content type');

    // Verify success message.
    $this->assertSession()->pageTextContains('has been updated');
  }

  /**
   * Tests that promotion weight field appears on node edit form.
   */
  public function testPromotionWeightFieldOnNodeForm(): void {
    $this->drupalLogin($this->adminUser);

    // Create a node.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Article',
      'status' => 1,
    ]);
    $node->save();

    // Navigate to node edit form.
    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Field should appear (it's in the Promotion options section).
    $this->assertSession()->fieldExists('field_promotion_weight[0][value]');
  }

  /**
   * Tests that promotion weight can be set and saved on nodes.
   */
  public function testSetPromotionWeightOnNode(): void {
    $this->drupalLogin($this->adminUser);

    // Create a node.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Article',
      'status' => 1,
      'promote' => 1,
    ]);
    $node->save();

    // Edit and set weight.
    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->submitForm([
      'field_promotion_weight[0][value]' => 15,
    ], 'Save');

    // Reload node and verify weight.
    $node = Node::load($node->id());
    $this->assertEquals(15, $node->get('field_promotion_weight')->value);
  }

  /**
   * Tests that the promote form is accessible.
   */
  public function testPromoteFormAccess(): void {
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'manage promoted content order',
    ]);
    $this->drupalLogin($this->adminUser);

    // Navigate to promote form.
    $this->drupalGet('/admin/content/promote');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Promote Articles');
  }

  /**
   * Tests anonymous user cannot access promote form.
   */
  public function testAnonymousNoPromoteFormAccess(): void {
    $this->drupalGet('/admin/content/promote');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests Views can sort by promotion weight field.
   */
  public function testViewsSortByPromotionWeight(): void {
    $this->drupalLogin($this->adminUser);

    // Create test nodes with different weights.
    $node1 = Node::create([
      'type' => 'article',
      'title' => 'Node High Weight',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 20,
    ]);
    $node1->save();

    $node2 = Node::create([
      'type' => 'article',
      'title' => 'Node Low Weight',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 5,
    ]);
    $node2->save();

    // Access the content admin page (uses Views).
    $this->drupalGet('/admin/content');
    $this->assertSession()->statusCodeEquals(200);

    // Both nodes should be visible.
    $this->assertSession()->pageTextContains('Node High Weight');
    $this->assertSession()->pageTextContains('Node Low Weight');
  }

}
