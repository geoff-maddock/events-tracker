-- Set your current window here:
SET @start := '2024-12-31';
SET @end   := '2025-12-31';  -- end is exclusive

SET @days := DATEDIFF(@end, @start);
SET @prev_start := DATE_SUB(@start, INTERVAL @days DAY);
SET @prev_end   := @start;

WITH
curr AS (
  SELECT
    et.tag_id,
    COUNT(DISTINCT e.id) AS curr_uses
  FROM event_tag et
  JOIN events e ON e.id = et.event_id
  WHERE e.cancelled_at IS NULL
    AND e.start_at >= @start AND e.start_at < @end
  GROUP BY et.tag_id
),
prev AS (
  SELECT
    et.tag_id,
    COUNT(DISTINCT e.id) AS prev_uses
  FROM event_tag et
  JOIN events e ON e.id = et.event_id
  WHERE e.cancelled_at IS NULL
    AND e.start_at >= @prev_start AND e.start_at < @prev_end
  GROUP BY et.tag_id
),
joined AS (
  SELECT
    t.id AS tag_id,
    t.name AS tag,
    COALESCE(curr.curr_uses, 0) AS curr_uses,
    COALESCE(prev.prev_uses, 0) AS prev_uses
  FROM tags t
  LEFT JOIN curr ON curr.tag_id = t.id
  LEFT JOIN prev ON prev.tag_id = t.id
  WHERE (COALESCE(curr.curr_uses, 0) > 0 OR COALESCE(prev.prev_uses, 0) > 0)
),
ranked AS (
  SELECT
    j.*,
    DENSE_RANK() OVER (ORDER BY j.curr_uses DESC) AS curr_rank,
    DENSE_RANK() OVER (ORDER BY j.prev_uses DESC) AS prev_rank
  FROM joined j
)
SELECT
  tag_id,
  tag,
  curr_uses,
  prev_uses,
  curr_rank,
  prev_rank,
  (CAST(prev_rank AS SIGNED) - CAST(curr_rank AS SIGNED)) AS steps_up,
  (curr_uses - prev_uses) AS delta_uses
FROM ranked
WHERE curr_uses > 5
ORDER BY steps_up DESC, curr_uses DESC
LIMIT 50;
