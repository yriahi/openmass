<?php

namespace Drupal\mayflower\Twig;

/**
 * Twig loader to load Mayflower templates from the library.
 */
class MayflowerLoader extends \Twig_Loader_Filesystem {

  const NAMESPACES = [
    'base' => '00-base',
    'atoms' => '01-atoms',
    'molecules' => '02-molecules',
    'organisms' => '03-organisms',
    'templates' => '04-templates',
    'pages' => '05-pages',
    'meta' => '07-meta'
  ];

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();
    $artifactsRoot = mayflower_get_path();
    foreach (self::NAMESPACES as $namespace => $folder) {
      $path = sprintf('%s/twig/%s', rtrim($artifactsRoot, '/'), $folder);
      try {
        $this->setPaths([$path], $namespace);
      }
      // Throw a more friendly error message.
      catch (\Twig_Error_Loader $e) {
        throw new \Twig_Error_Loader(sprintf('It looks like a Mayflower directory is not properly configured: %s', $e->getMessage()), -1, $e);
      }
    }
  }

}
