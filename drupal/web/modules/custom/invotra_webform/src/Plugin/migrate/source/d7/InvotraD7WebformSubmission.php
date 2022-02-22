<?php

namespace Drupal\invotra_webform\Plugin\migrate\source\d7;

use Drupal\migrate\Event\ImportAwareInterface;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\webform\Entity\Webform;

/**
 * Drupal 7 webform submission source from database.
 *
 * @MigrateSource(
 *   id = "invotra_d7_webform_submission",
 *   core = {7},
 *   source_module = "webform",
 *   destination_module = "webform"
 * )
 */
class InvotraD7WebformSubmission extends DrupalSqlBase implements ImportAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('webform_submissions', 'wfs');
    $query->innerJoin('webform', 'wf', 'wf.nid = wfs.nid');
    $query->leftJoin('field_data_field_submission', 'fs', 'fs.field_submission_sid = wfs.sid AND fs.entity_type = \'node\'');
    $query->leftJoin('field_data_field_idea_reference', 'ir', 'ir.entity_type = fs.entity_type AND ir.entity_id = fs.entity_id');
    $query->leftJoin('field_data_field_query_reference', 'qr', 'qr.entity_type = fs.entity_type AND qr.entity_id = fs.entity_id');

    $query->fields('wfs', [
      'nid',
      'sid',
      'uid',
      'submitted',
      'remote_addr',
      'is_draft',
    ]);
    $query->fields('wf', ['machine_name']);
    $query->addExpression('IFNULL(SUBSTRING(IFNULL(ir.field_idea_reference_value, IFNULL(qr.field_query_reference_value, NULL)), 4), wfs.serial)', 'serial');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Webform node Id'),
      'sid' => $this->t('Webform submission Id'),
      'uid' => $this->t('User Id of submitter'),
      'serial' => $this->t('The serial number of this submission.'),
      'machine_name' => $this->t('The machine name of the webform'),
      'submitted' => $this->t('Submission timestamp'),
      'remote_addr' => $this->t('IP Address of submitter'),
      'is_draft' => $this->t('Whether this submission is draft'),
      'webform_id' => $this->t('Id to be used for Webform'),
      'webform_data' => $this->t('Webform submitted data'),
      'webform_uri' => $this->t('Submission uri'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $sid = $row->getSourceProperty('sid');
    $machine_name = $row->getSourceProperty('machine_name');
    $submitted_data = $this->buildSubmittedData($sid);
    $row->setSourceProperty('webform_id', $this->getWebformId($nid, $machine_name));
    $row->setSourceProperty('webform_data', $submitted_data);
    $row->setSourceProperty('webform_uri', '/form/webform-' . $nid);
    return parent::prepareRow($row);
  }

  /**
   * Prepare webform ID according to the node ID and machine name.
   *
   * @param string $nid
   *   Node ID.
   * @param string $machine_name
   *   Webform machine name.
   *
   * @return string
   *   A new webform ID.
   */
  private function getWebformId(string $nid, string $machine_name): string {
    switch ($machine_name) {
      case 'ideas':
        $webform_id = 'idea';
        break;

      case 'queries':
        $webform_id = 'query';
        break;

      default:
        $webform_id = 'webform_' . $nid;
    }

    return $webform_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['sid']['type'] = 'integer';
    $ids['sid']['alias'] = 'wfs';
    return $ids;
  }

  /**
   * Build submitted data from webform submitted data table.
   */
  private function buildSubmittedData($sid) {
    $query = $this->select('webform_submitted_data', 'wfsd');
    $query->innerJoin('webform_component', 'wc', 'wc.nid=wfsd.nid AND wc.cid=wfsd.cid');

    $query->fields('wfsd', [
      'no',
      'data',
    ])
      ->fields('wc', [
        'form_key',
        'extra',
      ]);
    $wf_submissions = $query->condition('sid', $sid)->execute();

    $submitted_data = [];
    foreach ($wf_submissions as $wf_submission) {
      $extra = unserialize($wf_submission['extra']);
      if (!empty($extra['multiple']) && !empty($wf_submission['data'])) {
        $item[$wf_submission['no']] = $wf_submission['data'];
      }
      else {
        $item = $wf_submission['data'];
      }
      if (!empty($item)) {
        $submitted_data[$wf_submission['form_key']] = $item;
      }
    }
    return $submitted_data;
  }

  /**
   * {@inheritdoc}
   */
  public function preImport(MigrateImportEvent $event) {}

  /**
   * {@inheritdoc}
   */
  public function postImport(MigrateImportEvent $event) {
    $webform_count_tables = ['idea' => 'invotra_ideas_reference', 'query' => 'invotra_queries_reference'];
    foreach ($webform_count_tables as $webform_id => $count_table) {
      // Retrieve webform by ID.
      if (!($webform = Webform::load($webform_id))) {
        continue;
      }
      // Retrieve max serial of the webform submissions.
      /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
      $webform_storage = $this->entityTypeManager->getStorage('webform');
      $max_serial = $webform_storage->getMaxSerial($webform);
      $next_serial_current = $webform_storage->getNextSerial($webform);

      // Retrieve a next serial number.
      // @see InvotraD7 - invotra_ideas_reference_get_next_ref_num()
      // @see InvotraD7 - invotra_queries_reference_get_next_ref_num()
      $query = $this->select($count_table);
      $query->addExpression('MAX(value)');

      // FALSE will be converted to 0.
      $last_value = intval($query->execute()->fetchField());
      $next_serial = max($last_value + 1, $max_serial);

      // Set next serial number.
      // @see WebformEntitySettingsSubmissionsForm::save()
      if ($next_serial > $next_serial_current) {
        $webform_storage->setNextSerial($webform, $next_serial);
      }
    }
  }

}
