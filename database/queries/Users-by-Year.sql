SELECT
    YEAR(a.created_at) AS login_year,
    COUNT(DISTINCT a.user_id) AS number_of_users
FROM
    activities a
JOIN
    users u ON a.user_id = u.id
WHERE
    a.object_table = 'User'
    AND a.action_id = 4
GROUP BY
    YEAR(a.created_at)
ORDER BY
    login_year;