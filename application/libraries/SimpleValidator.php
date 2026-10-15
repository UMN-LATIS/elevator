<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SimpleValidator {
  /**
   * Validates $data against $schema (array of field => closure[])
   * Returns filtered data or throws ValidationException
   *
   * @example
   * ```php
   * use SimpleValidator as V;
   *
   * $data = [
   *  'name' => 'John Doe',
   *  'age' => 30,
   * ];
   * $schema = [
   *  'name' => [V::required(), V::minLength(3)],
   *  'age' => [V::required(), V::integer(), V::min(18)],
   * ];
   *
   * try {
   *  $validatedData = V::validate($data, $schema);
   * } catch (ValidationException $e) {
   *  $errors = $e->getErrors();
   * }
   */
  public static function validate(array $data, array $schema): array {
    $errors = [];

    foreach ($schema as $field => $checkers) {
      $value = $data[$field] ?? null;

      foreach ($checkers as $checker) {
        $result = $checker($value, $data);
        if ($result !== true) {
          $errors[$field][] = $result;
        }
      }
    }

    if (!empty($errors)) {
      throw new ValidationException($errors);
    }

    // return only the fields that are defined in the schema
    return array_intersect_key($data, $schema);
  }

  // ----- Validators

  public static function required(): \Closure {
    return fn($v) => (isset($v) && $v !== '') ? true : 'This field is required';
  }

  public static function integer(): \Closure {
    return fn($v) => !isset($v) || filter_var($v, FILTER_VALIDATE_INT) !== false ? true : 'Must be an integer';
  }

  public static function array(): \Closure {
    return fn($v) => !isset($v) || is_array($v) ? true : 'Must be an array';
  }

  public static function string(): \Closure {
    return fn($v) => !isset($v) || is_string($v) ? true : 'Must be a string';
  }

  public static function in(array $allowedValues): \Closure {
    return fn($v) => !isset($v) || in_array($v, $allowedValues, true)
      ? true
      : 'Must be one of: ' . implode(', ', $allowedValues);
  }

  /**
   * Applies $checkers to every value of an array field.
   *
   * @example
   * // permission[12]=9 checks '9'
   * 'permission' => [V::eachValue(V::integer())]
   */
  public static function eachValue(\Closure ...$checkers): \Closure {
    return function ($v, $data = []) use ($checkers) {
      if (!isset($v)) {
        return true;
      }
      if (!is_array($v)) {
        return 'Must be an array';
      }
      foreach ($v as $key => $item) {
        foreach ($checkers as $checker) {
          $result = $checker($item, $data);
          if ($result !== true) {
            return "Item {$key}: {$result}";
          }
        }
      }
      return true;
    };
  }

  /**
   * Applies $checkers to every key of an array field.
   *
   * @example
   * // permission[12]=9 checks the int 12
   * 'permission' => [V::eachKey(V::integer())]
   */
  public static function eachKey(\Closure ...$checkers): \Closure {
    return function ($v, $data = []) use ($checkers) {
      if (!isset($v)) {
        return true;
      }
      if (!is_array($v)) {
        return 'Must be an array';
      }
      foreach (array_keys($v) as $key) {
        foreach ($checkers as $checker) {
          $result = $checker($key, $data);
          if ($result !== true) {
            return "Key {$key}: {$result}";
          }
        }
      }
      return true;
    };
  }

  public static function min(int $min): \Closure {
    return fn($v) => !isset($v) || (is_numeric($v) && $v >= $min)
      ? true
      : "Must be at least {$min}";
  }

  public static function max(int $max): \Closure {
    return fn($v) => !isset($v) || (is_numeric($v) && $v <= $max)
      ? true
      : "Must not exceed {$max}";
  }

  public static function regex(string $pattern, $errorMessage = 'Invalid format.'): \Closure {
    return function ($v) use ($pattern, $errorMessage) {
      // absent stays valid, use required() to enforce presence
      if (!isset($v)) {
        return true;
      }
      // a non-string can't match a pattern, so report it rather than
      // letting preg_match throw a TypeError
      if (!is_string($v)) {
        return $errorMessage;
      }
      return preg_match($pattern, $v) ? true : $errorMessage;
    };
  }

  public static function notRegex(string $pattern, $errorMessage = 'String is invalid format.'): \Closure {
    return function ($v) use ($pattern, $errorMessage) {
      // absent stays valid, use required() to enforce presence
      if (!isset($v)) {
        return true;
      }
      // a non-string has no forbidden content to match, but the wrong
      // type is still a failure, so report it rather than let it pass
      if (!is_string($v)) {
        return $errorMessage;
      }
      return preg_match($pattern, $v) ? $errorMessage : true;
    };
  }

  public static function minLength(int $length): \Closure {
    return fn($v) => !isset($v) || (is_string($v) && mb_strlen($v) >= $length)
      ? true
      : "Must be at least {$length} characters long";
  }

  public static function maxLength(int $length): \Closure {
    return fn($v) => !isset($v) || (is_string($v) && mb_strlen($v) <= $length)
      ? true
      : "Must be {$length} characters or fewer";
  }

  public static function json(): \Closure {
    return function ($v) {
      if (!isset($v)) return true;
      if (!is_string($v)) return 'Must be valid JSON';
      json_decode($v);
      return json_last_error() === JSON_ERROR_NONE ? true : 'Must be valid JSON';
    };
  }
}
