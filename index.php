<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeoDash - Sistema de Geolocalização</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --bg-light: #f8f9fa;
        }

        body {
            background: var(--bg-light);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        #map {
            height: 500px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            cursor: crosshair;
        }

        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        .table-responsive {
            max-height: 450px;
            overflow-y: auto;
        }

        .table-responsive::-webkit-scrollbar {
            width: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9998;
        }

        .loading-overlay.show {
            display: flex;
        }

        .spinner-border-custom {
            width: 3rem;
            height: 3rem;
            border: 0.4em solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0a58ca 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .map-instruction {
            background: #e7f3ff;
            border-left: 4px solid var(--primary-color);
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .leaflet-popup-content-wrapper {
            border-radius: 8px;
        }

        .btn-group-sm>.btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border spinner-border-custom" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <div class="container-fluid py-4">
        <!-- Header -->
        <header class="d-flex flex-wrap justify-content-between align-items-center mb-4 px-3">
            <div>
                <h2 class="fw-bold text-dark mb-1">
                    <i class="bi bi-geo-alt-fill text-primary"></i>
                    GeoDash - Sistema de Geolocalização
                </h2>
                <p class="text-muted small mb-0">Contrato 93 - Colinas do Rei - Pouso Alegre / MG</p>
            </div>
            <div class="d-flex gap-2">
                <button id="importarLista" class="btn btn-success btn-sm btn-icon">
                    <i class="bi bi-upload"></i> Importar Dados
                </button>
                <button id="downloadCSV" class="btn btn-dark btn-sm btn-icon">
                    <i class="bi bi-download"></i> Exportar CSV
                </button>
            </div>
        </header>

        <div class="row g-4">
            <!-- Formulário -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center" id="formHeader">
                        <span>Novo Registro</span>
                        <span class="badge bg-secondary" id="totalPontos">0 pontos</span>
                    </div>
                    <div class="card-body">
                        <div class="map-instruction">
                            <i class="bi bi-info-circle"></i>
                            <strong>Dica:</strong> Clique no mapa para capturar coordenadas automaticamente
                        </div>

                        <form id="formMapa">
                            <input type="hidden" id="editId" value="-1">

                            <div class="mb-3">
                                <label class="form-label small fw-bold">
                                    <i class="bi bi-card-text"></i> Descrição
                                </label>
                                <input type="text" id="desc" class="form-control" placeholder="Ex: Placa de sinalização" required maxlength="200">
                                <div class="form-text">Máximo 200 caracteres</div>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold">
                                        <i class="bi bi-compass"></i> Latitude
                                    </label>
                                    <input type="number" step="any" id="lat" class="form-control" placeholder="-22.28" required>
                                    <div class="invalid-feedback">
                                        Latitude: -90 a 90
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold">
                                        <i class="bi bi-compass"></i> Longitude
                                    </label>
                                    <input type="number" step="any" id="lng" class="form-control" placeholder="-45.93" required>
                                    <div class="invalid-feedback">
                                        Longitude: -180 a 180
                                    </div>
                                </div>
                            </div>

                            <button type="submit" id="btnSalvar" class="btn btn-primary w-100 fw-bold btn-icon">
                                <i class="bi bi-save"></i> Gravar no Servidor
                            </button>
                            <button type="button" id="btnCancelar" class="btn btn-light w-100 mt-2 d-none btn-icon">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </button>
                        </form>

                        <hr class="my-3">

                        <div class="d-flex gap-2">
                            <button id="centralizarMapa" class="btn btn-outline-primary btn-sm flex-fill btn-icon">
                                <i class="bi bi-crosshair"></i> Centralizar
                            </button>
                            <button id="limparBanco" class="btn btn-outline-danger btn-sm flex-fill btn-icon">
                                <i class="bi bi-trash"></i> Limpar Tudo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mapa -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-3">
                        <div id="map"></div>
                    </div>
                </div>
            </div>

            <!-- Tabela -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white fw-bold py-3">
                        <i class="bi bi-table"></i> Registros Cadastrados
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle" id="tabelaPontos">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="ps-4" style="width: 5%">#</th>
                                        <th style="width: 40%">Descrição</th>
                                        <th style="width: 25%">Coordenadas</th>
                                        <th style="width: 15%">Data</th>
                                        <th class="text-center" style="width: 15%">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            Nenhum ponto cadastrado
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Configurações globais
        const API_URL = 'api.php';
        const CENTRO_INICIAL = [-22.28, -45.93];
        const ZOOM_INICIAL = 13;

        // Variáveis globais
        let map, markerLayer = L.layerGroup();
        let pontosData = [];

        // ============================================
        // FUNÇÕES DE UTILIDADE
        // ============================================

        /**
         * Exibe toast de notificação
         */
        function showToast(message, type = 'success') {
            const toastId = 'toast_' + Date.now();
            const bgColor = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-warning';
            const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'exclamation-circle';

            const toastHTML = `
                <div id="${toastId}" class="toast align-items-center text-white ${bgColor} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-${icon}"></i> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;

            $('#toastContainer').append(toastHTML);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, {
                delay: 4000
            });
            toast.show();

            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }

        /**
         * Mostra/esconde loading overlay
         */
        function toggleLoading(show = true) {
            if (show) {
                $('#loadingOverlay').addClass('show');
            } else {
                $('#loadingOverlay').removeClass('show');
            }
        }

        /**
         * Valida coordenadas geográficas
         */
        function validarCoordenadas(lat, lng) {
            lat = parseFloat(lat);
            lng = parseFloat(lng);

            if (isNaN(lat) || isNaN(lng)) return false;
            if (lat < -90 || lat > 90) return false;
            if (lng < -180 || lng > 180) return false;

            return true;
        }

        /**
         * Formata data para exibição
         */
        function formatarData(dataString) {
            if (!dataString) return '-';
            const data = new Date(dataString);
            return data.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        /**
         * Escapa HTML para prevenir XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ============================================
        // FUNÇÕES DO MAPA
        // ============================================

        /**
         * Inicializa o mapa Leaflet
         */
        function initMap() {
            map = L.map('map').setView(CENTRO_INICIAL, ZOOM_INICIAL);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            markerLayer.addTo(map);

            // Clique no mapa para capturar coordenadas
            map.on('click', function(e) {
                const {
                    lat,
                    lng
                } = e.latlng;
                $('#lat').val(lat.toFixed(6));
                $('#lng').val(lng.toFixed(6));
                $('#desc').focus();
                showToast('Coordenadas capturadas! Adicione uma descrição.', 'success');
            });
        }

        /**
         * Centraliza mapa em todos os pontos
         */
        function centralizarMapa() {
            if (pontosData.length === 0) {
                map.setView(CENTRO_INICIAL, ZOOM_INICIAL);
                showToast('Nenhum ponto para centralizar', 'warning');
                return;
            }

            const bounds = pontosData.map(p => [p.lat, p.lng]);
            map.fitBounds(bounds, {
                padding: [50, 50],
                maxZoom: 15
            });
        }

        // ============================================
        // FUNÇÕES DE API
        // ============================================

        /**
         * Carrega todos os pontos do servidor
         */
        function carregarDados() {
            toggleLoading(true);

            $.ajax({
                url: API_URL,
                method: 'GET',
                data: {
                    action: 'listar'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        pontosData = response.data || [];
                        atualizarInterface();
                    } else {
                        showToast(response.message || 'Erro ao carregar dados', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showToast('Erro de conexão com o servidor', 'error');
                    console.error('Erro:', error);
                },
                complete: function() {
                    toggleLoading(false);
                }
            });
        }

        /**
         * Atualiza toda a interface (tabela e mapa)
         */
        function atualizarInterface() {
            atualizarTabela();
            atualizarMapa();
            atualizarContadores();
        }

        /**
         * Atualiza tabela de pontos
         */
        function atualizarTabela() {
            const tbody = $('#tabelaPontos tbody').empty();

            if (pontosData.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Nenhum ponto cadastrado
                        </td>
                    </tr>
                `);
                return;
            }

            pontosData.forEach((p, index) => {
                const row = `
                    <tr data-id="${p.id}">
                        <td class="ps-4 fw-bold">${pontosData.length - index}</td>
                        <td><strong>${escapeHtml(p.descricao)}</strong></td>
                        <td>
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i> ${parseFloat(p.lat).toFixed(6)}, ${parseFloat(p.lng).toFixed(6)}
                            </small>
                        </td>
                        <td><small class="text-muted">${formatarData(p.data_criacao)}</small></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-primary btn-localizar" data-id="${p.id}" title="Localizar no mapa">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning btn-editar" data-id="${p.id}" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-deletar" data-id="${p.id}" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        /**
         * Atualiza marcadores no mapa
         */
        function atualizarMapa() {
            markerLayer.clearLayers();

            pontosData.forEach((p, index) => {
                const marker = L.marker([p.lat, p.lng], {
                    title: p.descricao
                }).addTo(markerLayer);

                const popupContent = `
                    <div class="p-2">
                        <strong>${escapeHtml(p.descricao)}</strong><br>
                        <small class="text-muted">
                            <i class="bi bi-geo-alt"></i> ${parseFloat(p.lat).toFixed(6)}, ${parseFloat(p.lng).toFixed(6)}
                        </small>
                    </div>
                `;
                marker.bindPopup(popupContent);
            });

            if (pontosData.length > 0) {
                centralizarMapa();
            }
        }

        /**
         * Atualiza contadores na interface
         */
        function atualizarContadores() {
            $('#totalPontos').text(`${pontosData.length} ${pontosData.length === 1 ? 'ponto' : 'pontos'}`);
        }

        /**
         * Salva ponto (novo ou edição)
         */
        function salvarPonto(dados) {
            toggleLoading(true);

            $.ajax({
                url: API_URL,
                method: 'POST',
                data: {
                    ...dados,
                    action: 'salvar'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, 'success');
                        resetForm();
                        carregarDados();
                    } else {
                        showToast(response.message || 'Erro ao salvar', 'error');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showToast(response?.message || 'Erro ao salvar ponto', 'error');
                },
                complete: function() {
                    toggleLoading(false);
                }
            });
        }

        /**
         * Deleta um ponto
         */
        function deletarPonto(id) {
            if (!confirm('Tem certeza que deseja excluir este ponto permanentemente?')) {
                return;
            }

            toggleLoading(true);

            $.ajax({
                url: API_URL,
                method: 'POST',
                data: {
                    id: id,
                    action: 'deletar'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, 'success');
                        carregarDados();
                    } else {
                        showToast(response.message || 'Erro ao deletar', 'error');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showToast(response?.message || 'Erro ao deletar ponto', 'error');
                },
                complete: function() {
                    toggleLoading(false);
                }
            });
        }

        /**
         * Limpa todo o banco de dados
         */
        function limparBanco() {
            if (!confirm('⚠️ ATENÇÃO: Esta ação irá apagar TODOS os pontos cadastrados permanentemente!\n\nDeseja continuar?')) {
                return;
            }

            toggleLoading(true);

            $.ajax({
                url: API_URL,
                method: 'GET',
                data: {
                    action: 'limpar'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, 'success');
                        pontosData = [];
                        atualizarInterface();
                    } else {
                        showToast(response.message || 'Erro ao limpar banco', 'error');
                    }
                },
                error: function() {
                    showToast('Erro ao limpar banco de dados', 'error');
                },
                complete: function() {
                    toggleLoading(false);
                }
            });
        }

        // ============================================
        // FUNÇÕES DE FORMULÁRIO
        // ============================================

        /**
         * Prepara formulário para edição
         */
        function prepararEdicao(id) {
            const ponto = pontosData.find(p => p.id == id);
            if (!ponto) return;

            $('#editId').val(ponto.id);
            $('#desc').val(ponto.descricao);
            $('#lat').val(ponto.lat);
            $('#lng').val(ponto.lng);

            $('#formHeader span:first').text('Editando Registro').addClass('text-warning');
            $('#btnSalvar').html('<i class="bi bi-save"></i> Atualizar Registro')
                .removeClass('btn-primary').addClass('btn-warning');
            $('#btnCancelar').removeClass('d-none');

            // Scroll suave para o formulário
            $('html, body').animate({
                scrollTop: $('#formMapa').offset().top - 100
            }, 500);

            // Destacar linha na tabela
            $('#tabelaPontos tbody tr').removeClass('table-warning');
            $(`#tabelaPontos tbody tr[data-id="${id}"]`).addClass('table-warning');
        }

        /**
         * Localiza ponto no mapa
         */
        function localizarNoMapa(id) {
            const ponto = pontosData.find(p => p.id == id);
            if (!ponto) return;

            map.setView([ponto.lat, ponto.lng], 17);

            // Abrir popup do marcador
            markerLayer.eachLayer(function(marker) {
                const pos = marker.getLatLng();
                if (pos.lat === ponto.lat && pos.lng === ponto.lng) {
                    marker.openPopup();
                }
            });

            showToast('Ponto localizado no mapa', 'success');
        }

        /**
         * Reseta formulário para estado inicial
         */
        function resetForm() {
            $('#formMapa')[0].reset();
            $('#editId').val('-1');
            $('#formHeader span:first').text('Novo Registro').removeClass('text-warning');
            $('#btnSalvar').html('<i class="bi bi-save"></i> Gravar no Servidor')
                .removeClass('btn-warning').addClass('btn-primary');
            $('#btnCancelar').addClass('d-none');
            $('#tabelaPontos tbody tr').removeClass('table-warning');

            // Remover classes de validação
            $('#lat, #lng').removeClass('is-invalid');
        }

        // ============================================
        // FUNÇÕES DE IMPORTAÇÃO/EXPORTAÇÃO
        // ============================================

        /**
         * Importa dados de exemplo
         */
        function importarDadosIniciais() {
            const dadosExemplo = [{
                    desc: "Placa de Sinalização - Entrada Principal",
                    lat: -22.2823,
                    lng: -45.9345
                },
                {
                    desc: "Lombada Eletrônica - Rua Principal",
                    lat: -22.2801,
                    lng: -45.9312
                },
                {
                    desc: "Faixa de Pedestres - Escola Municipal",
                    lat: -22.2789,
                    lng: -45.9298
                },
                {
                    desc: "Semáforo - Cruzamento Central",
                    lat: -22.2856,
                    lng: -45.9367
                },
                {
                    desc: "Buraco na Pista - Km 15",
                    lat: -22.2834,
                    lng: -45.9289
                }
            ];

            if (!confirm(`Importar ${dadosExemplo.length} pontos de exemplo?\n\nIsso não afetará os dados existentes.`)) {
                return;
            }

            toggleLoading(true);

            $.ajax({
                url: API_URL,
                method: 'POST',
                data: {
                    action: 'importar',
                    pontos: JSON.stringify(dadosExemplo)
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, 'success');
                        carregarDados();
                    } else {
                        showToast(response.message || 'Erro ao importar', 'error');
                    }
                },
                error: function() {
                    showToast('Erro ao importar dados', 'error');
                },
                complete: function() {
                    toggleLoading(false);
                }
            });
        }

        /**
         * Exporta dados para CSV
         */
        function exportarCSV() {
            if (pontosData.length === 0) {
                showToast('Nenhum dado para exportar', 'warning');
                return;
            }

            // Cabeçalho do CSV
            let csv = 'ID,Descrição,Latitude,Longitude,Data de Criação\n';

            // Adicionar dados
            pontosData.forEach(p => {
                const descricao = `"${p.descricao.replace(/"/g, '""')}"`;
                const dataFormatada = formatarData(p.data_criacao);
                csv += `${p.id},${descricao},${p.lat},${p.lng},"${dataFormatada}"\n`;
            });

            // Criar blob e download
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            link.setAttribute('href', url);
            link.setAttribute('download', `geodash_export_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showToast(`${pontosData.length} pontos exportados com sucesso!`, 'success');
        }

        // ============================================
        // EVENT HANDLERS
        // ============================================

        $(document).ready(function() {
            // Inicializar mapa e carregar dados
            initMap();
            carregarDados();

            // Submit do formulário
            $('#formMapa').on('submit', function(e) {
                e.preventDefault();

                const lat = $('#lat').val();
                const lng = $('#lng').val();

                // Validar coordenadas
                if (!validarCoordenadas(lat, lng)) {
                    $('#lat, #lng').addClass('is-invalid');
                    showToast('Coordenadas inválidas! Verifique os valores.', 'error');
                    return;
                }

                $('#lat, #lng').removeClass('is-invalid');

                const dados = {
                    id: $('#editId').val(),
                    desc: $('#desc').val().trim(),
                    lat: parseFloat(lat),
                    lng: parseFloat(lng)
                };

                salvarPonto(dados);
            });

            // Botão cancelar
            $('#btnCancelar').on('click', resetForm);

            // Botões de ação na tabela (delegação de eventos)
            $('#tabelaPontos').on('click', '.btn-editar', function() {
                const id = $(this).data('id');
                prepararEdicao(id);
            });

            $('#tabelaPontos').on('click', '.btn-deletar', function() {
                const id = $(this).data('id');
                deletarPonto(id);
            });

            $('#tabelaPontos').on('click', '.btn-localizar', function() {
                const id = $(this).data('id');
                localizarNoMapa(id);
            });

            // Botões de ação
            $('#limparBanco').on('click', limparBanco);
            $('#importarLista').on('click', importarDadosIniciais);
            $('#downloadCSV').on('click', exportarCSV);
            $('#centralizarMapa').on('click', centralizarMapa);

            // Validação em tempo real das coordenadas
            $('#lat, #lng').on('input', function() {
                const lat = $('#lat').val();
                const lng = $('#lng').val();

                if (lat && lng && !validarCoordenadas(lat, lng)) {
                    $(this).addClass('is-invalid');
                } else {
                    $('#lat, #lng').removeClass('is-invalid');
                }
            });
        });
    </script>
</body>

</html>