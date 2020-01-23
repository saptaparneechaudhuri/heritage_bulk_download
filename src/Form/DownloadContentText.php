<?php

namespace Drupal\heritage_bulk_download\Form;

use Drupal\Core\Form\FormBase;
use Drupal\file\Entity\File;
use Drupal\Core\Path\CurrentPathStack;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Archiver\ArchiverManager;
use Drupal\node\Entity\Node;
use Drupal\Core\Utility\LinkGeneratorInterface;




/**
 *
 */
class DownloadContentText extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */

  protected $currPath;

  /**
   * 
   *
   * @var pathLink\Drupal\Core\Utility\LinkGeneratorInterface
   * 
   */

  protected $pathLink;

 

  /**
   * Class constructor.
   *
   * 
   */
  public function __construct(CurrentPathStack $currPath, LinkGeneratorInterface $pathLink) {

    $this->currPath = $currPath;
    $this->pathLink = $pathLink;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(

      $container->get('path.current'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'heritage_bulk_download_content_text';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $texid = NULL) {

    // Get the textid from the current path.
    $path = $this->currPath->getPath();
    $arg = explode('/', $path);
    $textid = $arg[2];
    

    

    $form['text'] = [
      '#type' => 'hidden',
      '#value' => $textid,
    ];

    $form['text_info'] = [
      '#type' => 'container',
      '#prefix' => '<div id="text-info">',
      '#suffix' => '</div>',
    ];

    $form['text_info']['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select the Target Source'),
     // '#description' => $this->t('Choose the source you want to download'),
    ];

    $sources = [];

    $source_values = db_query("SELECT id, title FROM `heritage_source_info` WHERE text_id = :text_id", ['text_id' => $textid])->fetchAll();

    foreach ($source_values as $key => $value) {
      $sources[$value->id] = $value->title;
    }

    $form['text_info']['fieldset']['sources'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the source you want to download'),
      '#required' => TRUE,
      '#options' => $sources,
      '#default_value' => isset($form['text_info']['fieldset']['sources']['#default_value']) ? $form['text_info']['fieldset']['sources']['#default_value'] : NULL,

      '#ajax' => [
        'event' => 'change',
        'wrapper' => 'source-formats',
        'callback' => '::source_select',
      ],

    ];

    if (!empty($form_state->getTriggeringElement())) {
      $sourceid = $form_state->getUserInput()['sources'];
    }

    if (!isset($sourceid)) {
      $sourceid = $form['text_info']['fieldset']['sources']['#default_value'];
    }

    $form['text_info']['fieldset']['source_formats'] = [
      '#type' => 'container',
      '#prefix' => '<div id="source-formats">',
      '#suffix' => '</div>',
    ];

    if (isset($sourceid)) {
      $format = db_query("SELECT format FROM `heritage_source_info` WHERE id = :sourceid", [':sourceid' => $sourceid])->fetchField();

      $form['text_info']['fieldset']['format'] = [
        '#type' => 'hidden',
        '#value' => $format,
      ];

    }

    if (isset($format) && $format == 'text') {
      $form['text_info']['fieldset']['source_formats']['selected_langcode'] = [
        '#type' => 'language_select',
        '#title' => $this->t('Language'),
        '#languages' => LanguageInterface::STATE_CONFIGURABLE | LanguageInterface::STATE_SITE_DEFAULT,

      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download Content'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the text value
    $textid = $form_state->getValue('text');
    $textname = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();
    
    // Get the sourceid
    $sourceid = $form_state->getValue('sources');

    $field_name = 'field_'. $textname .'_'. $sourceid . '_text'; 
    $field_value = $field_name . '_value';

    $table_name = 'node__' . $field_name;

    $langcode = $form_state->getValue('selected_langcode');

    $contents = db_query("SELECT * FROM " . $table_name . " WHERE bundle = :textname AND langcode = :langcode",[':textname' => $textname,':langcode' => $langcode])->fetchAll();

    $var = '';

    for($i = 0;$i<count($contents);$i++) {
      $var = $var . $contents[$i]->{$field_value};
    }
   

   //print_r($var);exit;

    // Put the contents in a file, but conert the contents into string first
    $file = file_save_data($var, 'public://file_uploads/example.txt', FILE_EXISTS_RENAME);

    // TODO: check if the contents got written properly
    // Ask if the file extension could be saved as pdf




  }

  public function source_select(array $form, FormStateInterface $form_state) {
    return $form['text_info']['fieldset']['source_formats'];
  }

}
