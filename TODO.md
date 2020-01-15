# General TODOs, notes

## Modules
### media_entity_video
This might not be required and might be replaced with "file".
Although it adds some widegts, too, double-check is needed.

### video_embed_field

As described on the module page:
```
If you are installing this module for integration with a media library,
core already contains all the tools required for embedding remotely hosted videos.
This module should no longer be required for most use cases and should be avoided if possible.
For more information see the documentation for configuring remote video in core or migrating
to core media from Video Embed Field.
```

So we should consider removing it.

### media_entity_video

Add patch: https://www.drupal.org/project/media_entity_video/issues/2900466
Note, this likely conflicts with the already applied core fix patch.


### exception_mailer

Move patch to web (e.g d.org or github).


### filehash

Move patch to web (e.g d.org or github).

### exif

SXMP library is not available
