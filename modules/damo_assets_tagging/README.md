# DAMo - Image tagging

## Setup

- Add your key for Vision API to `private://keys/cloud-vision-service-account.json`
- Enable the module
- Enable the Cloud Vision Media settings for any media type you want. By default, this module enables it for Images.

## TODO

- google_cloud_vision_media adds a views_bulk_operations plugin for queuing the aut-tagging for selected media.
    - Require the module
    - Update the admin/content/media page with VBO
    - Fix front-end issues that arise with VBO 
