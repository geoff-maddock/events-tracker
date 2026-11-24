### Instagram captions in their own service (#2409)

The caption text of an Instagram post was built by `getInstagramFormat()` on `Event` and again on `Entity`, with the hashtag and handle rules written out in both. It now comes from `App\Services\Integrations\InstagramCaption` (`forEvent()`, `forEntity()`), which has those rules once. The captions themselves are unchanged.
