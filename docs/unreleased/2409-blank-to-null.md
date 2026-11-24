### One rule for empty dates and ids (#2409)

`Event`, `Series` and `Thread` had 21 mutators between them that all did the same thing: store an empty form field as null, and otherwise parse the date or keep the id. They are replaced by one trait, `App\Models\Concerns\BlankToNull`, and a list of the attributes on each model (`BLANK_TO_NULL_DATES`, `BLANK_TO_NULL_INTS`). Nothing changes on any page or in what is saved.
