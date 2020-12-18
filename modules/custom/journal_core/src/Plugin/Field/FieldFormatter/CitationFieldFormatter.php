<?php

namespace Drupal\journal_core\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\bibcite_entity\Entity\Reference;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bibcite\CitationStylerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\journal_core\CitationTools;

/**
 * Plugin implementation of the 'citation' formatter.
 *
 * @FieldFormatter(
 *   id = "citation",
 *   label = @Translation("Citation"),
 *   description = @Translation("Format using selected citation style."),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "text_long"
 *   }
 * )
 */
class CitationFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The styler service.
   *
   * @var \Drupal\bibcite\CitationStylerInterface
   */
  protected $styler;

  /**
   * The configuration manager service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface;
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Defines the default settings for this plugin.
   * Grabs the default citaiton style from the global Bibcite settings.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultSettings() {
    $config = \Drupal::config('bibcite.settings');
    return [
      'style' => $config->get('default_style'),
      'omit_title' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $csl_styles = $this->styler->getAvailableStyles();
    $styles_options = array_map(function ($entity) {
      /** @var \Drupal\bibcite\Entity\CslStyleInterface $entity */
      return $entity->label();
    }, $csl_styles);

    return [
        // Implement settings form.
        'style' => [
          '#type' => 'select',
          '#title' => $this->t('Citation style'),
          '#options' => $styles_options,
          '#default_value' => $this->getSetting('style'),
        ],
        'omit_title' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Omit article title'),
          '#description' => $this->t('Skip the article title when constructing the citation. Useful if this field appears along side a linked title field.'),
          '#default_value' => $this->getSetting('omit_title'),
        ],
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $csl_styles = $this->styler->getAvailableStyles();
    $current_style = $this->getSetting('style');
    $style_label = 'N/A';
    if (!empty($csl_styles[$current_style])) {
      $style_label = $csl_styles[$current_style]->get('label');
    }

    $summary[] = $this->t('Display the article citation using @style style', ['@style' => $style_label]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    //$reference = new Reference();

   // $r = Reference::create(['type' => 'book', 'author' => $item->getEntity()->get('field_article_contributors')]);
    //$r = entity_create('bibcite_reference', ['type' => 'book']);
    $field_map = \Drupal::service('entity_field.manager')->getFieldMap();
    $node_field_map = $field_map['node'];
    $entity = $item->getEntity();
    $citationTools = new CitationTools($this->entityTypeManager);
    $citation_metadata = $citationTools->getCitationMetadata($entity);

    if ($this->getSetting('omit_title')) {
      unset($citation_metadata['title']);
    }
    $r = Reference::create($citation_metadata);
    $styler = \Drupal::service('bibcite.citation_styler');

    $styler->setStyleById($this->getSetting('style'));

    $serializer = \Drupal::service('serializer');
    $data = $serializer->normalize($r, 'csl');

    $output = $styler->render($data);
    return $output;
  }


  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration management service.
   * @param CitationStylerInterface $styler
   *   Citation styler.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactoryInterface $config_factory, CitationStylerInterface $styler, EntityTypeManagerInterface $entity_type_manager) {
    $this->config_factory = $config_factory;
    $this->styler = $styler;
    $this->entityTypeManager = $entity_type_manager;
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * @inheritdoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('bibcite.citation_styler'),
      $container->get('entity_type.manager'));
  }
}
