<?php

namespace Drupal\heritage_bulk_download\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Url;

/**
 * Provides a block to Display Audio Play Options.
 *
 * @Block(
 *   id = "heritage_download_text",
 *   admin_label = @Translation("Download texts"),
 *   category = @Translation("Custom")
 * )
 */
class DisplayDownload extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */

  protected $currPath;


  /**
   * The link generator service.
   *
   * @var pathLink\Drupal\Core\Utility\LinkGeneratorInterface
   */

  protected $pathLink;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentPathStack $currPath, LinkGeneratorInterface $pathLink) {

    parent:: __construct($configuration, $plugin_id, $plugin_definition);
    $this->currPath = $currPath;
    $this->pathLink = $pathLink;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(

      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // $builtForm = \Drupal::formBuilder()->getForm('Drupal\heritage_bulk_download\Form\DownloadContentText');
    // $build = [];
    // $build['form'] = $builtForm;
    // $build['#cache']['max-age'] = 0;
    $path = $this->currPath->getPath();
    $arg = explode('/', $path);
    $textid = $arg[2];

    $build['link'] = [
      '#title' => 'Download Text',
      '#type' => 'link',
      '#url' => Url::fromRoute('heritage_bulk_download.downloadcontent', ['textid' => $textid]),
    ];

    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
