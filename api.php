<?php
$db = new PDO('sqlite:geodata.db');
$db->exec("CREATE TABLE IF NOT EXISTS pontos (id INTEGER PRIMARY KEY, desc TEXT, lat REAL, lng REAL)");

$action = $_REQUEST['action'] ?? '';

if ($action == 'list') {
    echo json_encode($db->query("SELECT * FROM pontos")->fetchAll(PDO::FETCH_ASSOC));
} 

elseif ($action == 'save') {
    $id = $_POST['id'] ?? -1;
    if ($id == -1) {
        $stmt = $db->prepare("INSERT INTO pontos (desc, lat, lng) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['desc'], $_POST['lat'], $_POST['lng']]);
    } else {
        $stmt = $db->prepare("UPDATE pontos SET desc=?, lat=?, lng=? WHERE id=?");
        $stmt->execute([$_POST['desc'], $_POST['lat'], $_POST['lng'], $id]);
    }
} 

elseif ($action == 'delete') {
    $stmt = $db->prepare("DELETE FROM pontos WHERE id=?");
    $stmt->execute([$_POST['id']]);
}
?>
