SELECT
  a.name AS artist_a,
  b.name AS artist_b,
  COUNT(*) AS times_together
FROM entity_event ea
JOIN entity_event eb
  ON eb.event_id = ea.event_id
 AND eb.entity_id > ea.entity_id
JOIN entities a ON a.id = ea.entity_id
JOIN entities b ON b.id = eb.entity_id
JOIN events e ON e.id = ea.event_id
WHERE e.start_at >= (NOW() - INTERVAL 12 MONTH)
  AND e.cancelled_at IS NULL
GROUP BY a.name, b.name
HAVING times_together >= 3
ORDER BY times_together DESC
LIMIT 100;