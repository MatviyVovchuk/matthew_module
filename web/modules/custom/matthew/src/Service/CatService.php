<?php

namespace Drupal\matthew\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;

/**
 * Service for managing cat-related operations.
 */
class CatService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CatService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entityTypeManager) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Retrieves a list of cat records from the database.
   *
   * @param array $conditions
   *   An associative array of conditions to filter the query, with keys as
   *   field names and values as the expected values.
   * @param array $fields
   *   An array of fields to retrieve from the database. Defaults to all fields.
   * @param bool $single
   *   Whether to fetch a single record. Defaults to FALSE.
   * @param string $order_by
   *   The field to order by. Defaults to 'created'.
   * @param string $order
   *   The sort direction ('ASC' or 'DESC'). Defaults to 'DESC'.
   *
   * @return array|object|null
   *   An array of cat records, a single record object,
   *   or NULL if no records found.
   */
  public function getCats(array $conditions = [], array $fields = [], bool $single = FALSE, string $order_by = 'created', string $order = 'DESC') {
    // Build the query to retrieve cat records.
    $query = $this->database->select('matthew', 'm');

    // If no specific fields are provided, select all fields.
    if (empty($fields)) {
      $query->fields('m');
    }
    else {
      $query->fields('m', $fields);
    }

    $query->orderBy($order_by, $order);

    // Apply conditions to the query.
    foreach ($conditions as $field => $value) {
      if (is_array($value)) {
        // Use 'IN' operator for array values.
        $query->condition($field, $value, 'IN');
      }
      else {
        // Use '=' operator for single values.
        $query->condition($field, $value);
      }
    }

    // Execute the query and return the results.
    if ($single) {
      return $query->execute()->fetchObject();
    }
    return $query->execute()->fetchAll();
  }

  /**
   * Saves a cat record with the given data.
   *
   * @param array $fields
   *   An associative array of fields to save,
   *   with keys as field names and values as the data.
   *
   * @throws \Exception
   *   Throws exception when something goes wrong.
   */
  public function saveCat(array $fields): void {
    // Load the file entity and set it as permanent.
    $file = $this->entityTypeManager->getStorage('file')->load($fields['cats_image_id']);
    if ($file instanceof File) {
      $file->setPermanent();
      $file->save();
    }

    // Save the cat data to the database.
    $this->database->insert('matthew')
      ->fields([
        'cat_name' => $fields['cat_name'],
        'user_email' => $fields['user_email'],
        'cats_image_id' => $file->id(),
        'created' => time(),
      ])->execute();
  }

  /**
   * Retrieves a single cat record by ID.
   *
   * @param int $id
   *   The ID of the cat.
   *
   * @return object|null
   *   The cat record, or NULL if not found.
   */
  public function getCatById($id): object|array|null
  {
    return $this->getCats(['id' => $id], ['id', 'cat_name', 'user_email'], TRUE);
  }

  /**
   * Retrieves multiple cat records by IDs.
   *
   * @param array $ids
   *   An array of cat IDs.
   *
   * @return array
   *   An array of cat records.
   */
  public function getCatsByIds(array $ids) {
    return $this->getCats(['id' => $ids], ['id', 'cat_name', 'user_email']);
  }

  /**
   * Deletes multiple cat records by IDs.
   *
   * @param array $ids
   *   An array of cat IDs to delete.
   *
   * @return int
   *   The number of affected rows.
   */
  public function deleteCatsByIds(array $ids): int
  {
    $conditions = ['id' => $ids];
    $fields = ['cats_image_id'];

    // Get image IDs before deleting records.
    $image_records = $this->getCats($conditions, $fields);

    // Extract the image IDs from the records.
    $image_ids = array_map(function ($record) {
      return $record->cats_image_id;
    }, $image_records);

    // Delete associated images.
    foreach ($image_ids as $fid) {
      if ($fid) {
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        $file?->delete();
      }
    }

    // Delete records where 'id' is in the array of IDs.
    return $this->database->delete('matthew')
      ->condition('id', $ids, 'IN')
      ->execute();
  }

  /**
   * Deletes a single cat record by ID.
   *
   * @param int $id
   *   The ID of the cat to delete.
   *
   * @return int
   *   The number of affected rows.
   */
  public function deleteCatById($id): int
  {
    $conditions = ['id' => $id];
    $fields = ['cats_image_id'];
    $single = TRUE;

    // Get image ID before deleting records.
    $image_record = $this->getCats($conditions, $fields, $single);

    // Delete associated image.
    if ($image_record && $image_record->cats_image_id) {
      // Load the file and delete it.
      $file = $this->entityTypeManager->getStorage('file')->load($image_record->cats_image_id);
      $file?->delete();
    }

    // Delete a record from the 'matthew' table where 'id' matches the given ID.
    return $this->database->delete('matthew')
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Updates a cat record by ID with new fields.
   *
   * @param int $id
   *   The ID of the cat to update.
   * @param array $fieldsToUpdate
   *   An associative array of fields to update,
   *   with keys as field names and values as new values.
   *
   * @return int
   *   The number of affected rows.
   */
  public function updateCatById($id, array $fieldsToUpdate) {
    $conditions = ['id' => $id];
    $fields = ['cats_image_id'];
    $single = TRUE;

    $cat_record = $this->getCats($conditions, $fields, $single);

    $old_file_id = $cat_record ? $cat_record->cats_image_id : NULL;

    // Handle the file upload.
    if ($file_id = $fieldsToUpdate['cats_image_id']) {
      // Load the new file.
      $file = $this->entityTypeManager->getStorage('file')->load($file_id);
      $file->setPermanent();
      $file->save();
      $fieldsToUpdate['cats_image_id'] = $file_id;

      // Delete the old file if it exists and is different from the new file.
      if ($old_file_id && $old_file_id != $file_id) {
        $old_file = $this->entityTypeManager->getStorage('file')->load($old_file_id);
        $old_file?->delete();
      }
    }

    // Update the 'matthew' table with the provided fields
    // where 'id' matches the given ID.
    return $this->database->update('matthew')
      ->fields($fieldsToUpdate)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Get the render array for a given file entity ID.
   *
   * @param mixed $file_id
   *   The ID of the file entity, or null if the field is empty.
   * @param string $image_style
   *   The image style to apply.
   *
   * @return array
   *   A render array for the image,
   *   or an empty array if the file could not be loaded.
   */
  public function getImageFileRenderArray(mixed $file_id, string $image_style): array {
    if (empty($file_id)) {
      return [];
    }

    // Load the file entity using the file ID.
    $file = $this->entityTypeManager->getStorage('file')->load($file_id);

    if ($file) {
      return [
        '#theme' => 'image_style',
        '#style_name' => $image_style,
        '#uri' => $file->getFileUri(),
        '#alt' => $file->getFilename(),
        '#attributes' => [
          'class' => ['cat-image'],
        ],
      ];
    }

    // Return an empty array if the file entity could not be loaded.
    return [];
  }

}
