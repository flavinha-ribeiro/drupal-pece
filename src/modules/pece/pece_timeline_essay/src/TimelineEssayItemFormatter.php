<?php

namespace Drupal\pece_timeline_essay;

class TimelineEssayItemFormatter {

  protected $timelineEssay;

  function __construct(\EntityDrupalWrapper $TimelineNode) {
    $this->timelineEssay = $TimelineNode;
  }

  public function getArtifactMediaFile(\EntityDrupalWrapper $entityWpr) {
    $artifact_field = array(
      'pece_artifact_audio' => 'field_pece_media_audio',
      'pece_artifact_image' => 'field_pece_media_image',
      'pece_artifact_video' => 'field_pece_media_video',
      'pece_artifact_website' => 'field_pece_website_url',
      'pece_artifact_pdf' => 'field_pece_media_pdf',
      'pece_artifact_text' => 'body',
    );
    if (!in_array($entityWpr->getBundle(), array_keys($artifact_field))) {
      return '';
    }
    $field = $artifact_field[$entityWpr->getBundle()];
    return $entityWpr->$field->value();
  }

  public function prepareTimelineItemMedia(\EntityDrupalWrapper $entityWpr) {
    $file = ($this->hasfield($entityWpr, 'field_pece_media_image'))
      ? $this->getRenderedField($entityWpr, 'field_pece_media_image')
      : $this->getArtifactMediaFile($entityWpr->field_pece_timeline_artifact);
    $thumbnail = ($this->hasField($entityWpr, 'field_thumbnail'))
      ? $this->getRenderedField($entityWpr, 'field_thumbnail')
      : FALSE;
    $media = array(
      'file' => $file,
      'caption' => $this->prepareTimelineItemCaption($entityWpr),
      'credit' => $this->prepareTimelineItemCredits($entityWpr),
    );
    if ($thumbnail) {
      $media['thumbnail'] = $thumbnail;
    }
    return $media;
  }

  protected function hasfield(\EntityDrupalWrapper $timelineItem, $field_name) {
    return null !== $timelineItem->$field_name->value();
  }

  protected function getRenderedField(\EntityDrupalWrapper $timelineItem, $field_name) {
    $field = field_view_field('pece_timeline_essay_item', $timelineItem->value(), $field_name);
    return drupal_render($field);
  }

  public function prepareTimelineItemCaption(\EntityDrupalWrapper $entityWpr){
    return $entityWpr->field_pece_timeline_caption->value();
  }

  public function prepareTimelineItemCredits(\EntityDrupalWrapper $entityWpr) {
    return drupal_render(field_view_field('pece_timeline_essay_item', $entityWpr->value(), 'field_image_credits'));
  }

  /**
   * Append node link to a given content.
   *
   * @param $TimelineItem Drupal entity
   * @param $content Entity field value
   * @return string
   * @throws \Exception
   */
  public function appendArtifactLink(\EntityDrupalWrapper $TimelineItem, $content) {
    $artifact_path = drupal_get_path_alias('node/' . $TimelineItem->field_pece_timeline_artifact->nid->value());
    $node_link = array(
      'text' => 'Read more',
      'path' => url($artifact_path, array('absolute' => TRUE)),
      'options' => array(
        'attributes' => array(),
        'html' => FALSE,
      ),
    );
    $link_wrapper = array(
      '#theme' => 'html_tag',
      '#tag' => 'p',
      '#value' => theme('link', $node_link),
      '#attributes' => array(
        'class' => 'pece-tle-item-link',
      ),
    );
    return $content . drupal_render($link_wrapper);
  }

  /**
   * Format a given date to TimelineJS date object structure.
   * @see: http://timeline.knightlab.com/docs/json-format.html
   */
  public function formatDate($timestamp) {
    $default = array(
      'year' => date('Y', $timestamp),
      'month' => date('m', $timestamp),
      'day' => date('d', $timestamp),
    );
    return $default;
  }

  /**
   * Format a given value pair to TimelineJS standard field object structure.
   * @see: http://timeline.knightlab.com/docs/json-format.html
   *
   * @$type string TimelineJS field type
   * @$value string Content
   * @return array
   */
  public function formatTlField($type, $value) {
    $default = array(
      $type => $value,
    );
    return $default;
  }

  /**
   * Format color to TimelineJS background object structure.
   */
  public function formatBgColor($color) {
    return $this->formatTlField('color', $color);
  }

  /**
   * Format text to TimelineJS text object structure.
   */
  public function formatText($raw_data) {
    return $this->formatTlField('text', $raw_data) ;
  }

  /**
   * Format media to TimelineJS media object structure.
   */
  public function formatMedia($media_settings) {
    $file_path = '';
    if (isset($media_settings['file']) && isset($media_settings['file']['uri'])) {
      $file_path = file_create_url($media_settings['file']['uri']);
    }
    $default = array(
      'url'=> $file_path,
      'caption' => $media_settings['caption'],
      'credit' => $media_settings['credit'],
    );
    return $default;
  }
}