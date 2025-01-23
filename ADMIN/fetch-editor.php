<?php
include '../config.php';

// Fetch editors and their workload
$query = "
    SELECT u.id, u.username, u.expertise_area, 
           COUNT(s.id) AS assigned_submissions
    FROM users u
    LEFT JOIN submissions s ON u.id = s.editor_id
    WHERE u.role = 'Editor'
    AND (s.status IS NULL OR s.status NOT IN ('Published', 'Rejected', 'Accepted'))
    GROUP BY u.id
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$editors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($editors);
?>
