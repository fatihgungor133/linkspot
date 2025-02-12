<?php
require_once '../config/database.php';
session_start();

// Oturum kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Kullanıcı bilgilerini al
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Kullanıcının linklerini al
$links_query = "SELECT * FROM links WHERE user_id = ? ORDER BY order_number ASC";
$stmt = $db->prepare($links_query);
$stmt->execute([$user_id]);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Son 7 günlük ziyaret istatistiklerini al
$stats_query = "SELECT COUNT(*) as visit_count, DATE(visited_at) as visit_date 
                FROM visits 
                WHERE user_id = ? 
                AND visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(visited_at)
                ORDER BY visit_date DESC";
$stmt = $db->prepare($stats_query);
$stmt->execute([$user_id]);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel - LinkSpot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">LinkSpot Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i> Ayarlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Çıkış
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Sol Sidebar -->
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="<?php echo !empty($user['profile_image']) ? '../' . htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/150' ?>" 
                             class="rounded-circle mb-3" 
                             style="width: 100px; height: 100px; object-fit: cover;">
                        <h5 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($user['profile_title'] ?? ''); ?></p>
                        <a href="profile.php" class="btn btn-sm btn-outline-primary">Profili Düzenle</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Hızlı İstatistikler</h6>
                        <p class="mb-2">Toplam Link: <?php echo count($links); ?></p>
                        <p class="mb-2">Aktif Link: <?php echo array_reduce($links, function($carry, $link) {
                            return $carry + ($link['is_active'] ? 1 : 0);
                        }, 0); ?></p>
                        <p class="mb-0">Son 7 Gün Ziyaret: <?php echo array_reduce($visits, function($carry, $visit) {
                            return $carry + $visit['visit_count'];
                        }, 0); ?></p>
                    </div>
                </div>
            </div>

            <!-- Ana İçerik -->
            <div class="col-md-9">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Linklerim</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLinkModal">
                            <i class="bi bi-plus"></i> Yeni Link Ekle
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($links)): ?>
                            <p class="text-center text-muted my-5">Henüz link eklenmemiş. Hemen yeni bir link ekleyin!</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($links as $link): ?>
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                         data-link-id="<?php echo $link['id']; ?>"
                                         data-active="<?php echo $link['is_active']; ?>">
                                        <div>
                                            <i class="bi bi-<?php echo htmlspecialchars($link['icon'] ?? 'link'); ?>"></i>
                                            <span class="ms-2 link-title"><?php echo htmlspecialchars($link['title']); ?></span>
                                            <span class="link-url" data-url="<?php echo htmlspecialchars($link['url']); ?>" style="display: none;"></span>
                                            <?php if (!$link['is_active']): ?>
                                                <span class="badge bg-secondary ms-2">Pasif</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" 
                                               class="btn btn-sm btn-outline-secondary me-1">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editLink(<?php echo $link['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteLink(<?php echo $link['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ziyaret İstatistikleri -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Son 7 Gün Ziyaret İstatistikleri</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="visitChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Yeni Link Ekleme Modal -->
    <div class="modal fade" id="addLinkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Link Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addLinkForm" action="add_link.php" method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Başlık</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="url" class="form-label">URL</label>
                            <input type="url" class="form-control" id="url" name="url" required>
                        </div>
                        <div class="mb-3">
                            <label for="icon" class="form-label">İkon (Bootstrap Icons)</label>
                            <input type="text" class="form-control" id="icon" name="icon" placeholder="örn: facebook">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" form="addLinkForm" class="btn btn-primary">Ekle</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Link Düzenleme Modal -->
    <div class="modal fade" id="editLinkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Link Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editLinkForm" action="edit_link.php" method="POST">
                        <input type="hidden" id="editLinkId" name="link_id">
                        <div class="mb-3">
                            <label for="editTitle" class="form-label">Başlık</label>
                            <input type="text" class="form-control" id="editTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="editUrl" class="form-label">URL</label>
                            <input type="url" class="form-control" id="editUrl" name="url" required>
                        </div>
                        <div class="mb-3">
                            <label for="editIcon" class="form-label">İkon (Bootstrap Icons)</label>
                            <input type="text" class="form-control" id="editIcon" name="icon" placeholder="örn: facebook">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="editIsActive" name="is_active" value="1">
                            <label class="form-check-label" for="editIsActive">Aktif</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" form="editLinkForm" class="btn btn-primary">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Ziyaret grafiği
        const visitData = <?php echo json_encode($visits); ?>;
        const ctx = document.getElementById('visitChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: visitData.map(item => item.visit_date),
                datasets: [{
                    label: 'Ziyaret Sayısı',
                    data: visitData.map(item => item.visit_count),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Link düzenleme fonksiyonu
        function editLink(id) {
            // Mevcut link bilgilerini al
            const linkElement = document.querySelector(`[data-link-id="${id}"]`);
            const title = linkElement.querySelector('.link-title').textContent;
            const url = linkElement.querySelector('.link-url').dataset.url;
            const icon = linkElement.querySelector('.bi').className.replace('bi bi-', '');
            const isActive = linkElement.dataset.active === '1';

            // Modal içeriğini güncelle
            document.getElementById('editLinkId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editUrl').value = url;
            document.getElementById('editIcon').value = icon;
            document.getElementById('editIsActive').checked = isActive;

            // Modalı aç
            const editModal = new bootstrap.Modal(document.getElementById('editLinkModal'));
            editModal.show();
        }

        // Link silme fonksiyonu
        function deleteLink(id) {
            if (confirm('Bu linki silmek istediğinizden emin misiniz?')) {
                fetch('delete_link.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `link_id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Linki listeden kaldır
                        const linkElement = document.querySelector(`[data-link-id="${id}"]`);
                        linkElement.remove();
                        
                        // Başarılı mesajı göster
                        showAlert('success', data.message);
                        
                        // Link sayısını güncelle
                        updateLinkCount();
                    } else {
                        showAlert('danger', data.message);
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Bir hata oluştu. Lütfen tekrar deneyin.');
                });
            }
        }

        // Link düzenleme formunu gönder
        document.getElementById('editLinkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('edit_link.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Link elementini güncelle
                    const linkElement = document.querySelector(`[data-link-id="${data.link.id}"]`);
                    linkElement.querySelector('.link-title').textContent = data.link.title;
                    linkElement.querySelector('.link-url').dataset.url = data.link.url;
                    linkElement.querySelector('.bi').className = `bi bi-${data.link.icon || 'link'}`;
                    linkElement.dataset.active = data.link.is_active;
                    
                    // Modalı kapat
                    bootstrap.Modal.getInstance(document.getElementById('editLinkModal')).hide();
                    
                    // Başarılı mesajı göster
                    showAlert('success', data.message);
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Bir hata oluştu. Lütfen tekrar deneyin.');
            });
        });

        // Yeni link ekleme formunu gönder
        document.getElementById('addLinkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('add_link.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Yeni linki listeye ekle
                    const linksList = document.querySelector('.list-group');
                    const emptyMessage = document.querySelector('.text-center.text-muted');
                    if (emptyMessage) {
                        emptyMessage.remove();
                    }
                    
                    const newLink = createLinkElement(data.link);
                    if (linksList.children.length === 0) {
                        linksList.appendChild(newLink);
                    } else {
                        linksList.insertBefore(newLink, linksList.firstChild);
                    }
                    
                    // Formu temizle ve modalı kapat
                    this.reset();
                    bootstrap.Modal.getInstance(document.getElementById('addLinkModal')).hide();
                    
                    // Başarılı mesajı göster
                    showAlert('success', data.message);
                    
                    // Link sayısını güncelle
                    updateLinkCount();
                } else {
                    showAlert('danger', data.message);
                    console.error('Link ekleme hatası:', data);
                }
            })
            .catch(error => {
                showAlert('danger', 'Bir hata oluştu. Lütfen tekrar deneyin.');
                console.error('Link ekleme hatası:', error);
            });
        });

        function createLinkElement(link) {
            const div = document.createElement('div');
            div.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            div.dataset.linkId = link.id;
            div.dataset.active = link.is_active || 1;
            
            div.innerHTML = `
                <div>
                    <i class="bi bi-${link.icon || 'link'}"></i>
                    <span class="ms-2 link-title">${link.title}</span>
                    <span class="link-url" data-url="${link.url}" style="display: none;"></span>
                </div>
                <div>
                    <a href="${link.url}" target="_blank" class="btn btn-sm btn-outline-secondary me-1">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editLink(${link.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteLink(${link.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            
            return div;
        }

        function updateLinkCount() {
            const totalLinks = document.querySelectorAll('[data-link-id]').length;
            document.querySelector('p.mb-2:first-child').textContent = `Toplam Link: ${totalLinks}`;
            
            const activeLinks = document.querySelectorAll('[data-link-id][data-active="1"]').length;
            document.querySelector('p.mb-2:nth-child(2)').textContent = `Aktif Link: ${activeLinks}`;
        }

        // Yardımcı fonksiyonlar
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // 3 saniye sonra alertı kaldır
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    </script>
</body>
</html> 