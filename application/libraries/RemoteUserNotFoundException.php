<?php
defined('BASEPATH') or exit('No direct script access allowed');

class RemoteUserNotFoundException extends RuntimeException
{
  protected string $remoteUserId;

  public function __construct(string $remoteUserId)
  {
    parent::__construct(
      "Could not find or provision user with remote id {$remoteUserId}",
      404
    );
    $this->remoteUserId = $remoteUserId;
  }

  public function getRemoteUserId(): string
  {
    return $this->remoteUserId;
  }
}
