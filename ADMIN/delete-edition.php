<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the ID is provided and valid
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid edition ID.'
        ]);
        exit;
    }

    $editionId = (int)$_POST['id'];

    try {
        // Check if the edition exists
        $stmt = $pdo->prepare("SELECT * FROM editions WHERE id = ?");
        $stmt->execute([$editionId]);
        $edition = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$edition) {
            echo json_encode([
                'success' => false,
                'message' => 'Edition not found.'
            ]);
            exit;
        }

        // Delete the edition record
        $deleteStmt = $pdo->prepare("DELETE FROM editions WHERE id = ?");
        $deleteStmt->execute([$editionId]);

        echo json_encode([
            'success' => true,
            'message' => 'Edition deleted successfully.'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting edition: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>
