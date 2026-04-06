<?php
// --- LÓGICA DE BACK-END (PHP + SQLITE) ---
$dbFile = 'mapa_dados.db';
try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar tabela se não existir
    $db->exec("CREATE TABLE IF NOT EXISTS pontos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, 
        descricao TEXT, 
        lat REAL, 
        lng REAL
    )");
} catch (PDOException $e) {
    die("Erro ao conectar ao banco: " . $e->getMessage());
}

// Processar requisições AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] == 'listar') {
        $stmt = $db->query("SELECT * FROM pontos ORDER BY id DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($_GET['action'] == 'salvar') {
        $id = $_POST['id'] ?? -1;
        $desc = $_POST['desc'] ?? '';
        $lat = $_POST['lat'] ?? 0;
        $lng = $_POST['lng'] ?? 0;

        if ($id == -1) {
            $stmt = $db->prepare("INSERT INTO pontos (descricao, lat, lng) VALUES (?, ?, ?)");
            $stmt->execute([$desc, $lat, $lng]);
        } else {
            $stmt = $db->prepare("UPDATE pontos SET descricao=?, lat=?, lng=? WHERE id=?");
            $stmt->execute([$desc, $lat, $lng, $id]);
        }
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($_GET['action'] == 'deletar') {
        $stmt = $db->prepare("DELETE FROM pontos WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($_GET['action'] == 'limpar') {
        $db->exec("DELETE FROM pontos");
        echo json_encode(['status' => 'success']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>GeoDash - Impacto Sinalização e Conservação Viária</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background: #f8f9fa;
        }

        #map {
            height: 450px;
            width: 100%;
            border-radius: 10px;
            shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }

        .table-responsive {
            max-height: 400px;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-4">
        <header class="d-flex justify-content-between align-items-center mb-4 px-3">
            <h2 class="fw-bold text-dark">📍 Contrato 93 - Colinas do Rei - Pouso Alegre / MG</h2>
            <div>
                <button id="importarLista" class="btn btn-success btn-sm me-2">Importar Dados Iniciais</button>
                <button id="downloadCSV" class="btn btn-dark btn-sm">Exportar CSV</button>
            </div>
        </header>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white fw-bold py-3" id="formHeader">Novo Registro</div>
                    <div class="card-body">
                        <form id="formMapa">
                            <input type="hidden" id="editId" value="-1">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Descrição</label>
                                <input type="text" id="desc" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold">Latitude</label>
                                    <input type="number" step="any" id="lat" class="form-control" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold">Longitude</label>
                                    <input type="number" step="any" id="lng" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" id="btnSalvar" class="btn btn-primary w-100 fw-bold">Gravar no Servidor</button>
                            <button type="button" id="btnCancelar" class="btn btn-light w-100 mt-2 d-none">Cancelar</button>
                        </form>
                        <hr>
                        <button id="limparBanco" class="btn btn-outline-danger btn-sm w-100">Zerar Banco de Dados</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div id="map"></div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tabelaPontos">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Descrição</th>
                                        <th>Lat/Lng</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let map, markerLayer = L.layerGroup();

        function initMap() {
            map = L.map('map').setView([-22.28, -45.93], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            markerLayer.addTo(map);
        }

        function carregarDados() {
            $.get('index.php?action=listar', function(data) {
                // Atualizar Tabela
                const tbody = $('#tabelaPontos tbody').empty();
                markerLayer.clearLayers();
                const bounds = [];

                data.forEach(p => {
                    // Adicionar na Tabela
                    tbody.append(`
                <tr>
                    <td class="ps-4"><strong>${p.descricao}</strong></td>
                    <td><small>${p.lat}, ${p.lng}</small></td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm" onclick='prepararEdicao(${JSON.stringify(p)})'>Editar</button>
                        <button class="btn btn-danger btn-sm" onclick="deletar(${p.id})">Excluir</button>
                    </td>
                </tr>
            `);
                    // Adicionar no Mapa
                    const marker = L.marker([p.lat, p.lng]).addTo(markerLayer);
                    marker.bindPopup(`<b>${p.descricao}</b>`);
                    bounds.push([p.lat, p.lng]);
                });

                if (bounds.length > 0) map.fitBounds(bounds, {
                    padding: [30, 30]
                });
            });
        }

        $('#formMapa').submit(function(e) {
            e.preventDefault();
            const dados = {
                id: $('#editId').val(),
                desc: $('#desc').val(),
                lat: $('#lat').val(),
                lng: $('#lng').val()
            };
            $.post('index.php?action=salvar', dados, function() {
                resetForm();
                carregarDados();
            });
        });

        function deletar(id) {
            if (confirm("Excluir permanentemente?")) {
                $.post('index.php?action=deletar', {
                    id: id
                }, carregarDados);
            }
        }

        function prepararEdicao(p) {
            $('#editId').val(p.id);
            $('#desc').val(p.descricao);
            $('#lat').val(p.lat);
            $('#lng').val(p.lng);
            $('#formHeader').text("Editando Registro").addClass('text-warning');
            $('#btnSalvar').text("Atualizar").addClass('btn-warning');
            $('#btnCancelar').removeClass('d-none');
            window.scrollTo(0, 0);
        }

        function resetForm() {
            $('#formMapa')[0].reset();
            $('#editId').val("-1");
            $('#formHeader').text("Novo Registro").removeClass('text-warning');
            $('#btnSalvar').text("Gravar no Servidor").removeClass('btn-warning');
            $('#btnCancelar').addClass('d-none');
        }

        $('#btnCancelar').click(resetForm);

        $('#limparBanco').click(function() {
            if (confirm("CUIDADO: Apagar todos os dados do SQLite?")) {
                $.get('index.php?action=limpar', carregarDados);
            }
        });

        $(document).ready(function() {
            initMap();
            carregarDados();
        });
    </script>

</body>

</html>