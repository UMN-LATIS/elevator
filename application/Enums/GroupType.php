<?php

namespace App\Enums;

if (! defined('BASEPATH')) exit('No direct script access allowed');


enum GroupType: string {
  case All = ALL_TYPE;
  case User = USER_TYPE;
  case Remote = REMOTE_TYPE;
  case Authed = AUTHED_TYPE;

  public function label(): string {
    return match ($this) {
      self::All => 'Everyone',
      self::Authed => 'Authenticated Users',
      self::Remote => 'SSO Users',
      self::User => 'Specific Users',
    };
  }

  public function description() {
    return match ($this) {
      self::All => 'All visitors, including anonymous.',
      self::Authed => 'Authenticated local or SSO users',
      self::Remote => 'Centrally authenticated users (SSO)',
      self::User => 'Specific individuals',
    };
  }

  public function toArray(): array {
    return [
      'type' => $this->value,
      'label' => $this->label(),
      'description' => $this->description(),
    ];
  }
}
