<?php

use CRM_PivotReport_Entity as Entity;
use CRM_PivotReport_BAO_PivotReportCache as PivotReportCache;

/**
 * Reflects Chunk Status values used to process cache building with
 * continous calls.
 */
class CRM_PivotCache_PivotReportChunkStatus {

  /**
   * Entity value.
   */
  private $entity;

  /**
   * Offset value.
   *
   * @var int
   */
  private $offset;

  /**
   * MultiValues offset value.
   *
   * @var int
   */
  private $multiValuesOffset;

  /**
   * Index value.
   *
   * @var string
   */
  private $index;

  /**
   * Page value.
   *
   * @var int
   */
  private $page;

  /**
   * Pivot Count value.
   *
   * @var int
   */
  private $pivotCount;

  /**
   * Initializes Chunk Status object with latest values from cache
   * or with defaults if cache values don't exist.
   */
  public function __construct() {
    $values = PivotReportCache::getItem('admin', 'chunk_status');

    $supportedEntities = Entity::getSupportedEntities();
    $defaultEntity = array_shift($supportedEntities);

    $this->entity = !empty($values['entity']) ? $values['entity'] : $defaultEntity;
    $this->offset = !empty($values['offset']) ? $values['offset'] : 0;
    $this->multiValuesOffset = !empty($values['multiValuesOffset']) ? $values['multiValuesOffset'] : 0;
    $this->index = !empty($values['index']) ? $values['index'] : NULL;
    $this->page = !empty($values['page']) ? $values['page'] : NULL;
    $this->pivotCount = !empty($values['pivotCount']) ? $values['pivotCount'] : 0;
  }

  /**
   * Updates Chunk Status cached values.
   */
  public function update() {
    $statusValues = array(
      'entity' => $this->entity,
      'offset' => $this->offset,
      'multiValuesOffset' => $this->multiValuesOffset,
      'index' => $this->index,
      'page' => $this->page,
      'pivotCount' => $this->pivotCount,
    );

    PivotReportCache::setItem(
      $statusValues,
      'admin',
      'chunk_status'
    );
  }

  /**
   * Sets entity property on the next available entity (or NULL)
   * and resets other Chunk Status properties.
   */
  public function setupNextEntity() {
    $this->entity = $this->getNextEntity();
    $this->offset = 0;
    $this->multiValuesOffset = 0;
    $this->index = NULL;
    $this->page = NULL;
    $this->pivotCount = 0;
  }

  /**
   * Returns next available entity name or NULL if there is no more entities.
   *
   * @return string
   */
  private function getNextEntity() {
    $supportedEntities = Entity::getSupportedEntities();
    $index = array_search($this->entity, $supportedEntities) + 1;

    if ($index === count($supportedEntities)) {
      return NULL;
    }

    return $supportedEntities[$index];
  }

  /**
   * Gets current entity value.
   *
   * @return string|NULL
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Sets current entity value.
   *
   * @param string $value
   */
  public function setEntity($value) {
    $this->entity = $value;
  }

  /**
   * Gets current entity value.
   *
   * @return string|NULL
   */
  public function getOffset() {
    return $this->offset;
  }

  /**
   * Sets current offset value.
   *
   * @param string $value
   */
  public function setOffset($value) {
    $this->offset = $value;
  }

  /**
   * Gets current multiValuesOffset value.
   *
   * @return string|NULL
   */
  public function getMultiValuesOffset() {
    return $this->multiValuesOffset;
  }

  /**
   * Sets current multiValuesOffset value.
   *
   * @param string $value
   */
  public function setMultiValuesOffset($value) {
    $this->multiValuesOffset = $value;
  }

  /**
   * Gets current index value.
   *
   * @return string|NULL
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * Sets current index value.
   *
   * @param string $value
   */
  public function setIndex($value) {
    $this->index = $value;
  }

  /**
   * Gets current page value.
   *
   * @return string|NULL
   */
  public function getPage() {
    return $this->page;
  }

  /**
   * Sets current page value.
   *
   * @param string $value
   */
  public function setPage($value) {
    $this->page = $value;
  }

  /**
   * Gets current pivotCount value.
   *
   * @return string|NULL
   */
  public function getPivotCount() {
    return $this->pivotCount;
  }

  /**
   * Sets current pivotCount value.
   *
   * @param string $value
   */
  public function setPivotCount($value) {
    $this->pivotCount = $value;
  }
}
