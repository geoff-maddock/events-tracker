### One photos trait for the models (#2409)

`Event`, `Entity`, `Series`, `Blog` and `User` each carried their own copy of the `photos` relation, `addPhoto()`, `getPrimaryPhoto()` and the two primary photo URL helpers. They now share one trait, `App\Models\Concerns\OwnsPhotos`. Nothing changes on any page.

`Thread` and `Post` had the same methods, but there has never been a `photo_thread` or `photo_post` table, so calling them could only fail. Nothing called them, and they are removed.

Eight entries leave the PHPStan baseline.
