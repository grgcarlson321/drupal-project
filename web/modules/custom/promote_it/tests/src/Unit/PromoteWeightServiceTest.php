<?php

declare(strict_types=1);

namespace Drupal\Tests\promote_it\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\promote_it\Service\PromoteFieldHelperService;
use Drupal\promote_it\Service\PromoteWeightService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PromoteWeightService.
 *
 * @coversDefaultClass \Drupal\promote_it\Service\PromoteWeightService
 * @group promote_it
 */
class PromoteWeightServiceTest extends TestCase {

  /**
   * The mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mock field helper service.
   *
   * @var \Drupal\promote_it\Service\PromoteFieldHelperService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldHelper;

  /**
   * The service under test.
   *
   * @var \Drupal\promote_it\Service\PromoteWeightService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->fieldHelper = $this->createMock(PromoteFieldHelperService::class);

    $this->service = new PromoteWeightService(
      $this->entityTypeManager,
      $this->fieldHelper
    );
  }

  /**
   * Tests getWeightedEntities returns entities with promotion weight field.
   *
   * @covers ::getWeightedEntities
   */
  public function testGetWeightedEntities(): void {
    // Mock nodes with promotion weight field.
    $node1 = $this->createMockNode(1, 'article', 'field_promotion_weight', 10);
    $node2 = $this->createMockNode(2, 'article', 'field_promotion_weight', 5);
    $node3 = $this->createMockNode(3, 'article', 'field_promotion_weight', 15);

    // Mock the query.
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(TRUE)
      ->willReturnSelf();
    $query->expects($this->any())
      ->method('condition')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn([1, 2, 3]);

    // Mock node storage.
    $nodeStorage = $this->createMock(NodeStorageInterface::class);
    $nodeStorage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);
    $nodeStorage->expects($this->once())
      ->method('loadMultiple')
      ->with([1, 2, 3])
      ->willReturn([$node1, $node2, $node3]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($nodeStorage);

    // Mock field helper to return the weight field name.
    $this->fieldHelper->expects($this->any())
      ->method('getWeightFieldName')
      ->with('article')
      ->willReturn('field_promotion_weight');

    // Call the method.
    $result = $this->service->getWeightedEntities();

    // Verify results are keyed by nid with weight values.
    $expected = [
      1 => 10,
      2 => 5,
      3 => 15,
    ];

    $this->assertEquals($expected, $result);
  }

  /**
   * Tests getNextWeight returns max weight + 1.
   *
   * @covers ::getNextWeight
   */
  public function testGetNextWeight(): void {
    // Mock nodes with weights.
    $node1 = $this->createMockNode(1, 'article', 'field_promotion_weight', 10);
    $node2 = $this->createMockNode(2, 'article', 'field_promotion_weight', 25);

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->willReturnSelf();
    $query->expects($this->any())
      ->method('condition')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn([1, 2]);

    $nodeStorage = $this->createMock(NodeStorageInterface::class);
    $nodeStorage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);
    $nodeStorage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn([$node1, $node2]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->willReturn($nodeStorage);

    // Mock field helper to return the weight field name.
    $this->fieldHelper->expects($this->any())
      ->method('getWeightFieldName')
      ->with('article')
      ->willReturn('field_promotion_weight');

    // Max weight is 25, so next should be 26.
    $result = $this->service->getNextWeight();
    $this->assertEquals(26, $result);
  }

  /**
   * Helper to create a mock node with promotion weight field.
   */
  protected function createMockNode(int $nid, string $bundle, string $fieldName, int $weight): NodeInterface {
    $node = $this->createMock(NodeInterface::class);
    $node->expects($this->any())
      ->method('id')
      ->willReturn($nid);
    $node->expects($this->any())
      ->method('bundle')
      ->willReturn($bundle);
    $node->expects($this->any())
      ->method('hasField')
      ->with($fieldName)
      ->willReturn(TRUE);

    // Create a simple object with a value property to represent the field.
    $fieldValue = new \stdClass();
    $fieldValue->value = $weight;

    $node->expects($this->any())
      ->method('get')
      ->with($fieldName)
      ->willReturn($fieldValue);

    return $node;
  }

}
