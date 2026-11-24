### Faster event and entity search (#2393)

A search finds an event by its own text or through a related entity, tag, series, venue or promoter. That was one query with the six conditions joined by OR, which made MySQL read every event instead of using the text index, once for the page and once for the count. Entities had the same shape with tags and aliases.

The search now collects the matching ids first, each condition using its own index, and then loads the page from those ids. On the dev database, over 75 search terms (the 60 most used and 15 odd ones), the event part of a search takes about half the time (for "techno": 435 ms to 208 ms including the cards' related data) and the entity part likewise. A search that matches nearly everything, like a single letter, takes the same time as before.

The results are unchanged: the same rows in the same order for all 75 terms, as a guest, a member and an admin.
