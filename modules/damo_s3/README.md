# DAMO - S3

Integration with Amazon S3 mainly via the s3fs module.

## Setup
### S3

- Create a private bucket.
- Create credentials

### Drupal

- Require `drupal/s3fs`
- Enable this module
- Add these with the proper values into your `settings.php` (or similar) file (note, some values can be added via the UI and put into version control, but have been added here regardless):
```php
// AWS S3.
$config['s3fs.settings']['bucket'] = '<bucket name>';
$config['s3fs.settings']['region'] = '<bucket region>';
$config['s3fs.settings']['domain'] = '<optional; the domain name of your e.g cloudfront instance>';
$settings['s3fs.upload_as_private'] = TRUE;
$settings['s3fs.access_key'] = $config['awssdk.configuration']['aws_key'] = '<Key generated on AWS>';
$settings['s3fs.secret_key'] = $config['awssdk.configuration']['aws_secret'] = '<Secret generated on AWS>';
$settings['php_storage']['twig']['directory'] = '<path to a non-webroot folder, e.g ../private_files/storage/php>';
``` 
- Set your field storage and your global site storage to S3
    - `system.file.yml` -> `default_scheme: s3`
    - `field.storage.media.field_video_file.yml` -> `settings.uri_scheme: s3`
    - etc.
- Migrate your data to S3

## Notes

With this setup every asset is going to be hosted in S3 (including image styles).
JS, CSS, .. are still going to be served from Drupal.
