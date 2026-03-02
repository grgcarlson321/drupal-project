<?php

declare(strict_types=1);

namespace Drupal\Tests\promote_it\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Kernel tests for PromoteWeightService with field-based storage.
 *
 * @group promote_it
 */
class PromoteWeightServiceKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'promote_it',
    'node',
    'field',
    'text',
    'user',
    'system',
  ];

  /**
   * The PromoteWeightService instance.
   *
   * @var \Drupal\promote_it\Service\PromoteWeightService
   */
  protected $promoteWeightService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'node']);

    // Create article content type.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Create the field storage for promotion weight.
    FieldStorageConfig::create([
      'field_name' => 'field_promotion_weight',
      'entity_type' => 'node',
      'type' => 'promote_weight',
      'cardinality' => 1,
    ])->save();

    // Add field to article content type.
    FieldConfig::create([
      'field_name' => 'field_promotion_weight',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Promotion Weight',
    ])->save();

    $this->promoteWeightService = $this->container->get('promote_it.promote_weight_service');
  }

  /**
   * Tests getWeightedEntities returns promoted nodes with weights.
   */
  public function testGetWeightedEntities(): void {
    // Create test nodes with different weights.
    $node1 = Node::create([
      'type' => 'article',
      'title' => 'Node 1',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 10,
    ]);
    $node1->save();

    $node2 = Node::create([
      'type' => 'article',
      'title' => 'Node 2',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 5,
    ]);
    $node2->save();

    $node3 = Node::create([
      'type' => 'article',
      'title' => 'Node 3',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 15,
    ]);
    $node3->save();

    // Retrieve weighted entities.
    $result = $this->promoteWeightService->getWeightedEntities();

    // Verify all promoted nodes are returned with correct weights.
    $this->assertCount(3, $result);
    $this->assertEquals(10, $result[$node1->id()]);
    $this->assertEquals(5, $result[$node2->id()]);
    $this->assertEquals(15, $result[$node3->id()]);
  }

  /**
   * Tests saveWeightedEntities updates node field values.
   */
  public function testSaveWeightedEntities(): void {
    // Create test nodes.
    $node1 = Node::create([
      'type' => 'article',
      'title' => 'Node 1',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 0,
    ]);
    $node1->save();

    $node2 = Node::create([
      'type' => 'article',
      'title' => 'Node 2',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 0,
    ]);
    $node2->save();

    // Update weights via service.
    $entities = [
      ['nid' => $node1->id(), 'weight' => 20],
      ['nid' => $node2->id(), 'weight' => 10],
    ];
    $this->promoteWeightService->saveWeightedEntities($entities);

    // Reload nodes and verify weights.
    $node1 = Node::load($node1->id());
    $node2 = Node::load($node2->id());

    $this->assertEquals(20, $node1->get('field_promotion_weight')->value);
    $this->assertEquals(10, $node2->get('field_promotion_weight')->value);
  }

  /**
   * Tests getNextWeight returns max weight + 1.
   */
  public function testGetNextWeight(): void {
    // Create nodes with various weights.
    Node::create([
      'type' => 'article',
      'title' => 'Node 1',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 10,
    ])->save();

    Node::create([
      'type' => 'article',
      'title' => 'Node 2',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 25,
    ])->save();

    Node::create([
      'type' => 'article',
      'title' => 'Node 3',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 5,
    ])->save();

    // Get next weight (should be max + 1 = 26).
    $nextWeight = $this->promoteWeightService->getNextWeight();
    $this->assertEquals(26, $nextWeight);
  }

  /**
   * Tests getNextWeight returns 0 when no promoted nodes exist.
   */
  public function testGetNextWeightEmpty(): void {
    $nextWeight = $this->promoteWeightService->getNextWeight();
    $this->assertEquals(0, $nextWeight);
  }

  /**
   * Tests that unpromoted nodes are not included.
   */
  public function testUnpromotedNodesExcluded(): void {
    // Create promoted node.
    Node::create([
      'type' => 'article',
      'title' => 'Promoted',
      'status' => 1,
      'promote' => 1,
      'field_promotion_weight' => 10,
    ])->save();

    // Create unpromoted node.
    Node::create([
      'type' => 'article',
      'title' => 'Unpromoted',
      'status' => 1,
      'promote' => 0,
      'field_promotion_weight' => 5,
    ])->save();

    $result = $this->promoteWeightService->getWeightedEntities();

    // Only promoted node should be included.
    $this->assertCount(1, $result);
  }

}
