<?php
namespace ShibPoseur\Exception;

class ConfigurationException extends \Exception
{
  protected $message = 'Shibboleth handler invoked at an unconfigured location.';
}
?>
