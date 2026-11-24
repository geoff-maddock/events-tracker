### One helper behind the user's followed lists (#2409)

`User` built the same join on `follows` nine times: once for each list and each count of the entities, tags, series and threads a user follows. Those methods keep their names and now share one private helper. Nothing changes on any page.

The four `*_following_count` attributes (`entities_following_count` and the like) are removed. Nothing read them; the profile page uses `countEntitiesFollowing()` and its siblings.
