<?php

namespace Drupal\journal_cite_this\Plugin\Block;

use Drupal\journal_types\CitationTools;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\bibcite\Plugin\BibciteFormatManagerInterface;
use Drupal\bibcite_entity\Entity\Reference;


/**
 * Provides a 'CiteThisBlock' block.
 *
 * @Block(
 *  id = "cite_this_block",
 *  admin_label = @Translation("Cite this"),
 *  context = {
 *    "node" = @ContextDefinition(
 *      "entity:node",
 *      label = @Translation("Current Node")
 *    )
 *  }
 * )
 */
class CiteThisBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Bibcite format manager service.
   *
   * @var \Drupal\bibcite\Plugin\BibciteFormatManagerInterface
   */
  protected $formatManager;

  /**
   * Constructs a new CiteThisBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    BibciteFormatManagerInterface $bibcite_format_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->formatManager = $bibcite_format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.bibcite_format')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $formats = $this->formatManager->getExportDefinitions();

    $node = $this->getContextValue('node');
    if (empty($node) || $node->getType() !== 'journal_article') {
      return;
    }

    $nid_fld = $node->nid->getValue();
    $nid = $nid_fld[0]['value'];
    $citation_tools = new CitationTools();
    $citation_metadata = $citation_tools->getCitationMetadataForArticle($node);

    $citation_metadata['type'] = 'journal_article';


    $r = Reference::create($citation_metadata);
    $serializer = \Drupal::service('serializer');
    $data = $serializer->normalize($r, 'csl');

    $text_citation = [
      '#theme' => 'bibcite_citation',
      '#data' => $data,
    ];

    if (!empty($nid)) {
      $export_links = [
        '#theme' => 'links',
        //'#list_type' => 'ul',
        '#prefix' => '<h3>' . $this->t('Export as') . '</h3>',
        '#links' => [],
        //'#attributes' => ['class' => ['citation-export-links']],
        //'#wrapper_attributes' => ['class' => ['container']],
      ];
      foreach ($formats as $format) {
        // Endnote 7 export seems to be buggy.
        $export_links['#links'][] = [
          'title' => $format['label'],
          'url' => Url::fromRoute('journal_cite_this.journal_export_controller_export', ['bibcite_format' => $format['id'], 'entity_type' => 'node', 'entity' => $nid]),
        ];
      }
    }
    return [$text_citation, $export_links];
  }

}
