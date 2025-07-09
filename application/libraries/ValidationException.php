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
}
