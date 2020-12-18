<?php

namespace Drupal\journal_core;

use Drupal\bibcite_entity\Entity\Contributor;

class CitationTools {

  public function getCitationMetadata($entity) {
    switch ($entity->getType()) {
      case 'journal':
        return $this->getCitationMetadataForJournal($entity);
        break;
      case 'issue':
        return $this->getCitationMetadataForIssue($entity);
        break;
      case 'journal_article':
        return $this->getCitationMetadataForArticle($entity);
        break;
      default:
        return FALSE;
    }
  }

  /**
   * Get citation metadata from an Article eneity type.
   * @return array The Article metadata.
   */
  public function getCitationMetadataForArticle($article_entity) {
    $data = [];
    $data['bibcite_type_of_work'] = 'Journal article';
    $data['title'] = $article_entity->get('title')->getValue();
    $issue_entity_item = $article_entity->get('field_part_of')->first();

    $affiliations = $article_entity->get('field_authors_and_affiliations')->referencedEntities();
    $contributors = [];
    foreach($affiliations as $affiliation) {
      $contributors += $affiliation->get('field_affiliation_contributor')->getValue();
    }
    $bibcite_contributors = [];
    foreach($contributors as $contributor) {
      $data['author'][] = Contributor::create([
        'first_name' => $contributor['given'],
        'last_name' => $contributor['family'],
        'middle_name' => $contributor['middle'],

      ]);

    }


    if (!empty($issue_entity_item)) {
      $data['issue_entity_id'] = $issue_entity_item->get('target_id')->getValue();
    }
    $data['type'] = 'journal_article';
    if (!empty($data['issue_entity_id'])) {
      $data['issue_entity'] = $this->entityTypeManager->getStorage('node')
        ->load($data['issue_entity_id']);

      $data += $this->getCitationMetadataForIssue($data['issue_entity']);
    }
    return $data;
  }

  /**
   * Get citation metadata from a Issue eneity type.
   * @return array The Issue metadata.
   */
  public function getCitationMetadataForIssue($issue_entity) {
    $data = [];
    $data['bibcite_number'] = $issue_entity->get('field_issue_number')->getValue();
    $data['bibcite_year'] = $issue_entity->get('field_issue_publication_date')->date->format('Y');
    $data['bibcite_volume'] = $issue_entity->get('field_issue_volume')->getValue();

    $journal_item = $issue_entity->get('field_part_of')->first();
    if (!empty($journal_item)) {
      $data['journal_entity_id'] = $journal_item->get('target_id')->getValue();
    }
    if (!empty($data['journal_entity_id'])) {
      $data['journal_entity'] = $this->entityTypeManager->getStorage('node')
        ->load($data['journal_entity_id']);
      $data += $this->getCitationMetadataForJournal($data['journal_entity']);
    }

    $data['type'] = 'journal_article'; // bibcite does not have a distinct 'issue' bundle.
    return $data;
  }

  /**
   * Get citation metadata from a Journal eneity type.
   * @return array The Journal metadata.
   */
  public function getCitationMetadataForJournal($journal_entity) {
    $data = [];
    $data['bibcite_secondary_title'] = $journal_entity->get('title')->getValue();
    $data['journal_abbrev'] = $journal_entity->get('field_journal_abbrev')->getValue();
    $data['journal_initials'] = $journal_entity->get('field_journal_initials')->getValue();
    $data['type'] = 'journal';
    return $data;
  }

  /**
   * CitationTools constructor.
   *
   * @TODO make this a proper dependency injection.
   * @param $entity_type_manager
   */
  public function __construct($entity_type_manager = FALSE) {
    $this->entityTypeManager = $entity_type_manager ? $entity_type_manager : \Drupal::entityTypeManager();
  }
}
