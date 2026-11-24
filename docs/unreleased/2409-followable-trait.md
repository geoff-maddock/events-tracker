### One follow trait for the models (#2409)

`Entity`, `Series`, `Tag` and `Thread` each had their own `follows()`, `followedBy()` and `followers()`. They now share `App\Models\Concerns\Followable`. Nothing changes on any page.

Two small differences came with it: `Thread::follows()` now works (it pointed at a `follow_thread` table that never existed, and nothing used it), and checking whether a user follows a tag no longer loads the user a second time.
