<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfYaml offers convenience methods to load and dump YAML.
 *
 * @package    symfony
 * @subpackage yaml
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfYaml.class.php 8988 2008-05-15 20:24:26Z fabien $
 */
class Yaml
{
  static private $spec = '1.2';

  public function __construct()
  {
    log_message('debug', 'Yaml: Library initialized.');
  }

  /**
   * Sets the YAML specification version to use.
   *
   * @param string $version The YAML specification version
   */
  function setSpecVersion($version)
  {
    if (!in_array($version, array('1.1', '1.2')))
    {
      throw new InvalidArgumentException(sprintf('Version %s of the YAML specifications is not supported', $version));
    }

	self::$spec = $version;
  }

  /**
   * Gets the YAML specification version to use.
   *
   * @return string The YAML specification version
   */
  public function getSpecVersion()
  {
    return self::$spec;
  }

  /**
   * Loads YAML into a PHP array.
   *
   * The load method, when supplied with a YAML stream (string or file),
   * will do its best to convert YAML in a file into a PHP array.
   *
   *  Usage:
   *  <code>
   *   $array = sfYaml::load('config.yml');
   *   print_r($array);
   *  </code>
   *
   * @param string $input Path of YAML file or string containing YAML
   *
   * @return array The YAML converted to a PHP array
   *
   * @throws InvalidArgumentException If the YAML is not valid
   */
  function load($input)
  {
    $file = '';

    // if input is a file, process it
    if (strpos($input, "\n") === false && is_file($input))
    {
      log_message('debug', 'Yaml: - input is file: ' . $input);

      $file = $input;

      ob_start();
      $retval = include($input);
      $content = ob_get_clean();

      // if an array is returned by the config file assume it's in plain php form else in YAML
      $input = is_array($retval) ? $retval : $content;
    }

    // if an array is returned by the config file assume it's in plain php form else in YAML
    if (is_array($input))
    {
      return $input;
    }

    require_once dirname(__FILE__).'/../third_party/yaml/sfYamlParser.php';

    $yamlParser = new sfYamlParser();

    try
    {
      $ret = $yamlParser->parse($input);
    }
    catch (Exception $e)
    {
      throw new InvalidArgumentException(sprintf('Unable to parse %s: %s', $file ? sprintf('file "%s"', $file) : 'string', $e->getMessage()));
    }

    return $ret;
  }

  /**
   * Dumps a PHP array to a YAML string.
   *
   * The dump method, when supplied with an array, will do its best
   * to convert the array into friendly YAML.
   *
   * @param array   $array PHP array
   * @param integer $inline The level where you switch to inline YAML
   *
   * @return string A YAML string representing the original PHP array
   */
  public function dump($array, $inline = 2)
  {
    require_once dirname(__FILE__).'/../third_party/yaml/sfYamlDumper.php';

    $yaml = new sfYamlDumper();

    return $yaml->dump($array, $inline);
  }


  /**
   * Create a Yaml file
   *
   * Array must be passed to dump yo Yaml format and then
   * write a file with to the specified path.
   *
   * @param string	$file File name
   * @param string	$path Specific path to save the file
   * @param array	$array PHP array
   */
  public function write_yml($file, $array, $path = '')
  {
	$file = trim($file);

	if( $path != '' AND is_dir($path) )
	{
		$path = rtrim($path, '/') . '/';
	}
	else
	{
		$path = '';
	}

    require_once dirname(__FILE__).'/../third_party/yaml/sfYamlDumper.php';

    $dumper = new sfYamlDumper();

	$yaml = $dumper->dump($array, $inline = 2);

	return file_put_contents($path.$file, $yaml);
  }
}
