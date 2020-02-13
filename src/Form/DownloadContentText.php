<?php

namespace Drupal\heritage_bulk_download\Form;

// require_once __DIR__ . '/vendor/autoload.php';.
use Mpdf\Mpdf;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\node\Entity\Node;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   */

  protected $pathLink;

  /**
   * Class constructor.
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

    if (isset($textid) && $textid > 0) {
      // Find the textname.
      $textname = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();

      // Load the text node.
      $text_node = Node::load($textid);

      // Query to find the number of levels in a text.
      $levels = db_query("SELECT field_levels_value FROM `node__field_levels` WHERE entity_id = :textid and bundle = :bundle ", [':textid' => $textid, ':bundle' => 'heritage_text'])->fetchField();

      $form['levels'] = [
        '#type' => 'hidden',
        '#value' => $levels,
      ];

      $level_labels = explode(',', $text_node->field_level_labels->value);

      $form['text_info']['fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Select the Target Source'),
      // '#description' => $this->t('Choose the source you want to download'),
      ];

      // Query for chapters/kandas.
      $chapters = [];
      $query = db_query("SELECT * FROM `taxonomy_term_field_data` WHERE name LIKE '{$level_labels[0]}%' AND vid = :textname ORDER BY tid ASC", [':textname' => $textname])->fetchAll();

      // Set the default value to first chapter/kanda.
      $chapter_selected_tid = $query[0]->tid;

      foreach ($query as $key => $value) {
        $chapters[$value->tid] = $value->name;
      }

      if ($levels == 1) {

        $form['text_info']['fieldset']['chapterStart'] = [
          '#type' => 'select',
          '#title' => $this->t('Select From ' . $level_labels[0]),
          '#required' => TRUE,
          '#options' => $chapters,
          '#default_value' => $chapter_selected_tid,
        ];

        $form['text_info']['fieldset']['chapterEnd'] = [
          '#type' => 'select',
          '#title' => $this->t('Select To ' . $level_labels[0]),
         // '#required' => TRUE,
          '#options' => $chapters,
          '#default_value' => $chapter_selected_tid,
        ];

      }

      if ($levels == 2) {
        $form['text_info']['fieldset']['chapters'] = [
          '#type' => 'select',
          '#title' => $this->t('Select ' . $level_labels[0]),
          '#required' => TRUE,
          '#options' => $chapters,
          '#default_value' => $chapter_selected_tid,

          '#ajax' => [
            'event' => 'change',
            'wrapper' => 'chapter-formats',
            'callback' => '::_ajax_firstSublevel_callback',
          ],

        ];

        // Calculate the slokas.
        $slokas = [];

        // Ajax triggers when a chapter is selected.
        if (!empty($form_state->getTriggeringElement())) {
          // Gives the tid of the chapter.
          $chapter_selected_tid = $form_state->getUserInput()['chapters'];
        }

        $form['text_info']['fieldset']['chapter_formats'] = [
          '#type' => 'container',
          '#prefix' => '<div id="chapter-formats">',
          '#suffix' => '</div>',
        ];

        // Calculate the sublevels of each chapter.
        if (isset($chapter_selected_tid)) {
          $sub_level_count = calculate_sublevel($textname, $chapter_selected_tid);
          for ($i = 1; $i <= $sub_level_count; $i++) {
            $slokas[$i] = $level_labels[1] . " " . $i;
          }

        }

        $form['text_info']['fieldset']['chapter_formats']['slokaStart'] = [
          '#type' => 'select',
          '#title' => $this->t('Select From ' . $level_labels[1]),
          '#required' => TRUE,
          '#options' => $slokas,
          '#default_value' => 1,
        ];

        $form['text_info']['fieldset']['chapter_formats']['slokaEnd'] = [
          '#type' => 'select',
          '#title' => $this->t('To ' . $level_labels[1]),
          // '#required' => TRUE,
          '#default_value' => NULL,

          '#options' => $slokas,

        ];

      }

      if ($levels == 3) {

        // Query for sargas.
        $query_sarga = db_query("SELECT * FROM `taxonomy_term_field_data` WHERE name LIKE 'Sarga%' AND vid = :textname AND tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE parent_target_id = :parent_tid)", [':textname' => $textname, ':parent_tid' => $chapter_selected_tid])->fetchAll();

        $sarga_selected_tid = $query_sarga[0]->tid;

        $form['text_info']['fieldset']['chapters'] = [
          '#type' => 'select',
          '#title' => $this->t('Select ' . $level_labels[0]),
          '#required' => TRUE,
          '#options' => $chapters,
          '#default_value' => $chapter_selected_tid,
          '#ajax' => [
            'event' => 'change',
            'wrapper' => 'chapter-formats',
            'callback' => '::_ajax_firstSublevel_callback',

          ],
        ];

        // Ajax triggers when chapter is selected.
        if (!empty($form_state->getTriggeringElement())) {
          // Gives the tid of chapter.
          $chapter_selected_tid = $form_state->getUserInput()['chapters'];

        }

        // Sargas and slokas.
        $sargas = [];
        $slokas = [];

        $form['text_info']['fieldset']['chapter_formats'] = [
          '#type' => 'container',
          '#prefix' => '<div id="chapter-formats">',
          '#suffix' => '</div>',
        ];

        // Calculate the sargas for each kanda.
        if (isset($chapter_selected_tid)) {
          // Query for sarga.
          $query = db_query("SELECT * FROM `taxonomy_term_field_data` WHERE name LIKE 'Sarga%' AND vid = :textname AND tid IN (SELECT entity_id FROM `taxonomy_term__parent` WHERE parent_target_id = :parent_tid)", [':textname' => $textname, ':parent_tid' => $chapter_selected_tid])->fetchAll();

          $sarga_selected_tid = $query_sarga[0]->tid;

          foreach ($query as $key => $value) {
            $sargas[$value->tid] = $value->name;
          }

        }

        $form['text_info']['fieldset']['chapter_formats']['sargas'] = [
          '#type' => 'select',
          '#title' => $this->t('Select ' . $level_labels[1]),
          '#required' => TRUE,
          '#options' => $sargas,
          '#default_value' => $sarga_selected_tid,
          '#ajax' => [
            'event' => 'change',
            'wrapper' => 'sarga-formats',
            'callback' => '::_ajax_sarga_callback',
          ],

        ];

        $form['text_info']['fieldset']['sarga_formats'] = [
          '#type' => 'container',
          '#prefix' => '<div id="sarga-formats">',
          '#suffix' => '</div>',
        ];

        // Ajax triggers when a sarga is selected.
        if (!empty($form_state->getTriggeringElement())) {
          // Gives the tid of the sarga.
          $sarga_selected_tid = $form_state->getUserInput()['sargas'];

        }

        if (isset($sarga_selected_tid)) {
          $sub_level_count = calculate_sublevel($textname, $sarga_selected_tid);
          for ($i = 1; $i <= $sub_level_count; $i++) {
            $slokas[$i] = $level_labels[2] . " " . $i;

          }
        }

        $form['text_info']['fieldset']['sarga_formats']['slokaStart'] = [
          '#type' => 'select',
          '#title' => $this->t('Select ' . $level_labels[2]),
          '#required' => TRUE,
          '#options' => $slokas,

          '#default_value' => 1,

        ];

         $form['text_info']['fieldset']['sarga_formats']['slokaEnd'] = [
          '#type' => 'select',
          '#title' => $this->t('Select ' . $level_labels[2]),
         // '#required' => TRUE,
          '#options' => $slokas,

          '#default_value' => 1,

        ];

      }

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

    // Get the text value.
    $textid = $form_state->getValue('text');
    $textname = db_query("SELECT field_machine_name_value FROM `node__field_machine_name` WHERE entity_id = :textid", [':textid' => $textid])->fetchField();

    // Get the sourceid.
    $sourceid = $form_state->getValue('sources');

    // Get the source name to put it as a file name.
    $source_name = db_query("SELECT title FROM `heritage_source_info` WHERE id = :sourceid AND text_id = :textid", [':sourceid' => $sourceid, ':textid' => $textid])->fetchField();
    // print_r($source_name);exit;
    $field_name = 'field_' . $textname . '_' . $sourceid . '_text';
    $field_value = $field_name . '_value';

    $table_name = 'node__' . $field_name;

    $langcode = $form_state->getValue('selected_langcode');

    // Load the node from chapter and sloka selected
    // Get the contents of the selected field for that node.
    $levels = $form_state->getValue('levels');

    if ($levels == 1) {
      // TODO.
      $chapterStart_tid = $form_state->getValue('chapterStart');

      $chapterStart = get_chapter_number($textname, $chapterStart_tid);
     //  print_r($chapterStart);exit;
      $chapterEnd_tid = $form_state->getValue('chapterEnd');
      $chapterEnd = get_chapter_number($textname, $chapterEnd_tid);

      $contents = get_contents_one_level($chapterStart, $chapterEnd, $chapter, $textname, $langcode, $table_name,$field_name);

    }

    $chapter_selected_tid = $form_state->getValue('chapters');
    $chapter = get_chapter_number($textname, $chapter_selected_tid);

    if ($levels == 2) {
      $slokaStart = $form_state->getValue('slokaStart');
      $slokaEnd = $form_state->getValue('slokaEnd');

      $contents = get_contents_two_levels($slokaStart, $slokaEnd, $chapter, $textname, $langcode, $table_name,$field_name);

    }

    if ($levels == 3) {

      // TODO for levels == 3.
      $slokaStart = $form_state->getValue('slokaStart');
      $slokaEnd = $form_state->getValue('slokaEnd');
      $sarga_tid = $form_state->getValue('sargas');
      $sarga = get_chapter_number($textname,$sarga_tid);
     // print_r($sarga);exit;



      $contents = get_contents_three_levels($slokaStart,$slokaEnd,$chapter,$sarga,$textname,$langcode,$field_name);
      
    }
  //  print_r($contents);exit;

 
    $mpdf = new Mpdf(['tempDir' => 'sites/default/files/tmp']);
    $mpdf->WriteHTML($var);
    $filename = $source_name . '.pdf';
    $mpdf->Output($filename, 'D');
    // exit;.
  }

  /**
   *
   */
  public function _ajax_firstSublevel_callback(array $form, FormStateInterface $form_state) {
    return $form['text_info']['fieldset']['chapter_formats'];
  }

  /**
   *
   */
  public function source_select(array $form, FormStateInterface $form_state) {
    return $form['text_info']['fieldset']['source_formats'];
  }

}
