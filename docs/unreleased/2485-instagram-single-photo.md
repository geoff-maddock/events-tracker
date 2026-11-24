### Instagram: events with one photo are posted (#2485)

The scheduled Instagram post always built a carousel. An event with only its own primary photo, and no related venue or artist photo, made a carousel of one, which Instagram rejects. Those events failed three times and were then skipped for good.

Such an event now goes out as a single photo. The "post carousel" action on an event page does the same. The scheduled post also stops at 10 images, Instagram's limit for a carousel.
