<?php

namespace Drupal\damo_assets\Form;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_upload\Form\BulkMediaUploadForm as ContribForm;
use Exception;
use function explode;
use function file_get_contents;
use function file_save_data;
use function in_array;
use function preg_match;
use function trim;

/**
 * Customized version of the form.
 *
 * @package Drupal\damo_assets\Form
 *
 * @see \Drupal\media_upload\Form\BulkMediaUploadForm
 */
class BulkMediaUploadForm extends ContribForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    MediaTypeInterface $type = NULL
  ) {
    $form = parent::buildForm($form, $form_state, $type);


    $isImage = $type !== NULL && $type->id() === 'image';

    // @todo: Load these from type configs, don't hardcode.
    // @todo: Change to https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21Select.php/class/Select/8.8.x
    $form['category'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Category'),
      '#target_type' => 'taxonomy_term',
      '#required' => $isImage,
      '#tags' => TRUE,
      '#selection_handler' => 'default:taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => ['category'],
        'auto_create' => TRUE,
        'match_operator' => 'CONTAINS',
      ],
    ];

    $form['keywords'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Keywords'),
      '#target_type' => 'taxonomy_term',
      '#tags' => TRUE,
      '#selection_handler' => 'default:taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => ['keyword'],
        'auto_create' => TRUE,
        'match_operator' => 'CONTAINS',
      ],
      '#autocreate' => [
        'bundle' => 'keyword',
      ],
    ];

    if ($isImage) {
      $form['image_alt_text'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Image alt text'),
        '#size' => 60,
        '#maxlength' => 128,
        '#required' => TRUE,
      ];
    }

    // @todo: Add proper api to the media_upload module.
    // Push submit button to the end of the form.
    $submit = $form['submit'];
    unset($form['submit']);
    $form['submit'] = $submit;

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $errorFlag = FALSE;
      $fileCount = 0;
      $createdMedia = [];
      $values = $form_state->getValues();

      if (empty($values['dropzonejs']) || empty($values['dropzonejs']['uploaded_files'])) {
        $this->logger->warning('No documents were uploaded');
        $this->messenger()
          ->addMessage($this->t('No documents were uploaded'), 'warning');
        return;
      }

      /** @var array $files */
      $files = $values['dropzonejs']['uploaded_files'];

      $typeId = $values['media_type'];
      $targetFieldSettings = $this->getTargetFieldSettings($typeId);
      // Prepare destination. Patterned on protected method
      // FileItem::doGetUploadLocation and public method
      // FileItem::generateSampleValue.
      $fileDirectory = trim($targetFieldSettings['file_directory'], '/');
      // Replace tokens. As the tokens might contain HTML we convert
      // it to plain text.
      $fileDirectory = PlainTextOutput::renderFromHtml(
        $this->token->replace($fileDirectory)
      );
      $targetDirectory = $targetFieldSettings['uri_scheme'] . '://' . $fileDirectory;
      $this->fileSystem->prepareDirectory($targetDirectory, FileSystemInterface::CREATE_DIRECTORY);

      /** @var array $file */
      foreach ($files as $file) {
        $fileInfo = [];
        if (preg_match(static::FILENAME_REGEX, $file['filename'], $fileInfo) !== 1) {
          $errorFlag = TRUE;
          $this->logger->warning('@filename - Incorrect file name', ['@filename' => $file['filename']]);
          $this->messenger()
            ->addMessage($this->t('@filename - Incorrect file name', ['@filename' => $file['filename']]), 'warning');
          continue;
        }

        if (!in_array(
          $fileInfo[static::EXT_NAME],
          explode(' ', $targetFieldSettings['file_extensions']),
          FALSE
        )) {
          $errorFlag = TRUE;
          $this->logger->error('@filename - File extension is not allowed', ['@filename' => $file['filename']]);
          $this->messenger()
            ->addMessage($this->t('@filename - File extension is not allowed', ['@filename' => $file['filename']]), 'error');
          continue;
        }

        $destination = $targetDirectory . '/' . $file['filename'];
        $data = file_get_contents($file['path']);
        $fileEntity = file_save_data($data, $destination);

        if (FALSE === $fileEntity) {
          $errorFlag = TRUE;
          $this->logger->warning('@filename - File could not be saved.', [
            '@filename' => $file['filename'],
          ]);
          $this->messenger()
            ->addMessage('@filename - File could not be saved.', [
              '@filename' => $file['filename'],
            ], 'warning');
          continue;
        }

        $mediaValues = $this->getNewMediaValues($typeId, $fileInfo, $fileEntity);
        $mediaValues['field_category'] = $values['category'];
        $mediaValues['field_keywords'] = $values['keywords'];
        if ($typeId === 'image') {
          $fileTargetField = $this->getTargetFieldName($typeId);
          $mediaValues[$fileTargetField]['alt'] = $values['image_alt_text'];
        }

        $media = $this->mediaStorage->create($mediaValues);
        $media->save();
        $createdMedia[] = $media;
        $fileCount++;
      }

      $form_state->set('created_media', $createdMedia);
      if ($errorFlag && !$fileCount) {
        $this->logger->warning('No documents were uploaded');
        $this->messenger()
          ->addMessage($this->t('No documents were uploaded'), 'warning');
        return;
      }

      if ($errorFlag) {
        $this->logger->info('Some documents have not been uploaded');
        $this->messenger()
          ->addMessage($this->t('Some documents have not been uploaded'), 'warning');
        $this->logger->info('@fileCount documents have been uploaded', ['@fileCount' => $fileCount]);
        $this->messenger()
          ->addMessage($this->t('@fileCount documents have been uploaded', ['@fileCount' => $fileCount]));
        return;
      }

      $this->logger->info('@fileCount documents have been uploaded', ['@fileCount' => $fileCount]);
      $this->messenger()
        ->addMessage($this->t('@fileCount documents have been uploaded', ['@fileCount' => $fileCount]));
      return;
    }
    catch (Exception $e) {
      $this->logger->critical($e->getMessage());
      $this->messenger()->addMessage($e->getMessage(), 'error');

      return;
    }
  }

}
