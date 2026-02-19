SELECT
  COALESCE(l.neighborhood, l.city, 'unknown') AS area,
  COUNT(*) AS events
FROM events e
JOIN entities v ON v.id = e.venue_id
LEFT JOIN locations l ON l.entity_id = v.id
WHERE e.start_at >= (NOW() - INTERVAL 12 MONTH)
  AND e.cancelled_at IS NULL
GROUP BY area
ORDER BY events DESC;