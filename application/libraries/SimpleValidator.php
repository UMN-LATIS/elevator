<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SimpleValidator
{
  /**
   * Validates $data against $schema (array of field => closure[])
   * Returns filtered data or throws ValidationException
   */
  public static function validate(array $data, array $schema): array
  {
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

    return array_intersect_key($data, $schema);
  }

  // /**
  //  * Converts a string-based rule array into closure array
  //  * E.g. ['required', 'min:3'] → [closure(), closure()]
  //  */
  public static function rules(array $rules): array
  {
    return $rules;
    // return array_map(function ($rule) {
    //   if ($rule instanceof \Closure) return $rule;

    //   var_dump($rule);

    //   if (str_contains($rule, ':')) {
    //     [$name, $param] = explode(':', $rule, 2);
    //     return self::fromString($name, $param);
    //   }

    //   return self::fromString($rule);
    // }, $rules);
  }

  /**
   * Maps string rule names to closure factories
   */
  private static function fromString(string $rule, $param = null): \Closure
  {
    return match ($rule) {
      'required'    => self::required(),
      'integer'     => self::integer(),
      'min'         => self::min((int) $param),
      'max'         => self::max((int) $param),
      'regex'       => self::regex($param),
      'minLength', 'min_length' => self::minLength((int) $param),
      'array' => self::array(),
      default       => fn() => "Unknown validation rule: {$rule}"
    };
  }

  // —–––– Validators —––––

  public static function required(): \Closure
  {
    return fn($v) => (isset($v) && $v !== '') ? true : 'This field is required';
  }

  public static function integer(): \Closure
  {
    return fn($v) => !isset($v) || filter_var($v, FILTER_VALIDATE_INT) !== false ? true : 'Must be an integer';
  }

  public static function array(): \Closure
  {
    return fn($v) => !isset($v) || is_array($v) ? true : 'Must be an array';
  }

  public static function min(int $min): \Closure
  {
    return fn($v) => !isset($v) || (is_numeric($v) && $v >= $min)
      ? true
      : "Must be at least {$min}";
  }

  public static function max(int $max): \Closure
  {
    return fn($v) => !isset($v) || (is_numeric($v) && $v <= $max)
      ? true
      : "Must not exceed {$max}";
  }

  public static function regex(string $pattern): \Closure
  {
    return fn($v) => !isset($v) || preg_match($pattern, $v)
      ? true
      : "Value does not match pattern: {$pattern}";
  }

  public static function minLength(int $length): \Closure
  {
    return fn($v) => is_string($v) && mb_strlen($v) >= $length
      ? true
      : "Must be at least {$length} characters long";
  }
}
