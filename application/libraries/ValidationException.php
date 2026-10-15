<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ValidationException extends RuntimeException
{
  protected array $errors;

  public function __construct(array $errors)
  {
    parent::__construct('Validation failed', 422);
    $this->errors = $errors;
  }

  public function getErrors(): array
  {
    return $this->errors;
  }

  /**
   * Each error as "field: message". Messages can contain
   * submitted keys, so escape them before rendering HTML.
   */
  public function getErrorMessages(): array
  {
    $messages = [];
    foreach ($this->errors as $field => $fieldErrors) {
      foreach ($fieldErrors as $error) {
        $messages[] = "{$field}: {$error}";
      }
    }
    return $messages;
  }
}
