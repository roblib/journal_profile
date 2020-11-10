<?php

namespace Drupal\journal_cite_this\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\bibcite_export\Controller\ExportController;
use Drupal\bibcite\Plugin\BibciteFormatInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\journal_types\CitationTools;
use Drupal\bibcite_entity\Entity\Reference;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class JournalExportController.
 */
class JournalExportController extends ExportController {

  /**
   * Symfony\Component\Serializer\SerializerInterface definition.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * Export a Journal Article using the bibcite_export contorller by
   * generating a Reference type entity on the fly.
   *
   * @param $bibcite_format
   *   The export format to use
   *
   * @param $entity_type
   *   The entity type. For thi module it will always be 'node'.
   *   We keep this to retain compatibility with the parent class.
   *
   * @param $entity
   *   The Journal Article entity.
   *
   * @return string
   *   Return Hello string.
   */
  public function export(BibciteFormatInterface $bibcite_format, $entity_type, EntityInterface $entity) {
    // TODO: Allow for citing other types of content.
    if ($entity_type !== 'node' || $entity->getType() !== 'journal_article') {
      throw new NotFoundHttpException();
    }
    $citation_tools = new CitationTools($this->entityTypeManager);
    $citation_metadata = $citation_tools->getCitationMetadataForArticle($entity);

    $citation_metadata['type'] = 'journal_article';
    $r = Reference::create($citation_metadata);

    return parent::export($bibcite_format, 'reference', $r);
  }

}
