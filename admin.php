<?php
// Configuración
define('API_KEY', '1f098c7d68777348425d008055475b88');
define('JSON_FILE', 'movies.json');
define('ADMIN_PASSWORD', 'sergio461');
define('ITEMS_PER_PAGE', 12);

// Iniciar sesión
session_start();

// Autenticación
if (!isset($_SESSION['logged_in'])) {
    if (isset($_POST['password']) && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['logged_in'] = true;
        header('Location: ?op=list');
        exit;
    } elseif (isset($_POST['password'])) {
        $error = "Contraseña incorrecta";
    }
    
    // Mostrar formulario de login
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso al Panel</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; }
            .login-container { max-width: 400px; margin: 100px auto; }
            .login-card { border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        </style>
    </head>
    <body>
        <div class="container login-container">
            <div class="card login-card">
                <div class="card-body p-4 text-center">
                    <h2 class="mb-4">Panel de Administración</h2>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="POST" class="mt-4">
                        <div class="mb-3">
                            <input type="password" class="form-control form-control-lg" placeholder="Contraseña" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Funciones principales
function getMovies() {
    if (!file_exists(JSON_FILE)) {
        file_put_contents(JSON_FILE, json_encode(['movies' => []]));
    }
    $movies = json_decode(file_get_contents(JSON_FILE), true)['movies'] ?? [];
    return array_reverse($movies);
}

function saveMovies($movies) {
    $movies = array_reverse($movies);
    file_put_contents(JSON_FILE, json_encode(['movies' => $movies], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function exportMovies() {
    if (!file_exists(JSON_FILE)) return false;
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="movies_backup_'.date('Y-m-d').'.json"');
    header('Content-Length: ' . filesize(JSON_FILE));
    readfile(JSON_FILE);
    exit;
}

function importMovies($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    
    $content = file_get_contents($file['tmp_name']);
    $data = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['movies'])) return false;
    
    file_put_contents(JSON_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return true;
}

// Procesar operaciones
$op = $_GET['op'] ?? 'list';
$movies = getMovies();

if ($op === 'logout') {
    session_destroy();
    header('Location: ?op=login');
    exit;
}

// Operaciones CRUD
switch ($op) {
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $index = $_POST['index'] ?? null;
            $newMovie = [
                'titulo' => $_POST['titulo'],
                'imb' => $_POST['imb'],
                'img' => $_POST['img'],
                'servidores' => [],
                'categoria' => $_POST['categoria'] ?? []
            ];

            foreach ($_POST['server_title'] ?? [] as $i => $title) {
                if (!empty($title) && !empty($_POST['server_url'][$i])) {
                    $newMovie['servidores'][] = [
                        'title' => $title,
                        'url' => $_POST['server_url'][$i]
                    ];
                }
            }

            if ($index !== null && isset($movies[$index])) {
                $movies[$index] = $newMovie;
            } else {
                array_unshift($movies, $newMovie);
            }

            saveMovies($movies);
            header('Location: ?op=list');
            exit;
        }
        break;
        
    case 'delete':
        $index = $_GET['id'] ?? null;
        if ($index !== null && isset($movies[$index])) {
            array_splice($movies, $index, 1);
            saveMovies($movies);
        }
        header('Location: ?op=list');
        exit;
        
    case 'export':
        exportMovies();
        break;
        
    case 'import':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
            $success = importMovies($_FILES['backup_file']);
            header('Location: ?op=list&import='.($success ? 'success' : 'error'));
            exit;
        }
        break;
}

// Paginación y búsqueda
$current_page = max(1, $_GET['page'] ?? 1);
$search = $_GET['search'] ?? '';
$filteredMovies = array_filter($movies, function($movie) use ($search) {
    return empty($search) || 
           stripos($movie['titulo'], $search) !== false || 
           stripos(implode(', ', $movie['categoria']), $search) !== false;
});
$total_pages = ceil(count($filteredMovies) / ITEMS_PER_PAGE);
$paginatedMovies = array_slice($filteredMovies, ($current_page-1)*ITEMS_PER_PAGE, ITEMS_PER_PAGE);

$allGenres = ['Acción', 'Aventura', 'Animación', 'Comedia', 'Crimen', 'Documental', 
             'Drama', 'Familia', 'Fantasía', 'Historia', 'Terror', 'Música', 
             'Misterio', 'Romance', 'Ciencia ficción', 'TV Movie', 'Thriller', 
             'Guerra', 'Suspense', 'Western'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Películas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-width: 220px;
        }
        
        body {
            background-color: #f5f5f5;
            padding-top: 60px;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            bottom: 0;
            left: -220px;
            z-index: 1000;
            padding: 20px 0;
            background: #2c3e50;
            transition: all 0.3s;
            overflow-y: auto;
        }
        
        .sidebar.show {
            left: 0;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
        }
        
        .main-content {
            transition: margin-left 0.3s;
        }
        
        .movie-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .movie-poster {
            height: 280px;
            object-fit: cover;
            width: 100%;
        }
        
        .movie-title {
            font-size: 1rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .genre-badge {
            font-size: 0.7rem;
            margin-right: 3px;
            margin-bottom: 3px;
        }
        
        .nav-link {
            color: #ecf0f1;
            border-radius: 5px;
            margin: 2px 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        
        .nav-link:hover, .nav-link.active {
            background: #3498db;
            color: white;
        }
        
        .nav-link i {
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }
        
        .navbar-brand {
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .movie-poster {
                height: 220px;
            }
            
            .sidebar {
                width: 80%;
                left: -80%;
            }
            
            .sidebar.show {
                width: 80%;
                left: 0;
            }
        }
        
        @media (min-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .sidebar {
                left: 0;
            }
            
            .sidebar.show + .main-content {
                margin-left: var(--sidebar-width);
            }
        }
    </style>
</head>
<body>
    <!-- Navbar Mobile -->
    <nav class="navbar navbar-dark bg-dark fixed-top d-lg-none">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-brand">CineAdmin</span>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar text-white" id="sidebar">
        <div class="text-center mt-3 mb-4">
            <h5 class="text-white">CineAdmin</h5>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $op === 'list' ? 'active' : '' ?>" href="?op=list">
                    <i class="bi bi-film"></i> Películas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= in_array($op, ['add', 'edit']) ? 'active' : '' ?>" href="?op=add">
                    <i class="bi bi-plus-circle"></i> Agregar
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="?op=logout">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </li>
        </ul>
    </div>

    <!-- Main content -->
    <div class="main-content" id="mainContent">
        <div class="container-fluid p-3">
            <?php if (isset($_GET['import'])): ?>
                <div class="alert alert-<?= $_GET['import'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <?= $_GET['import'] === 'success' ? 'Copia de seguridad importada correctamente' : 'Error al importar la copia de seguridad' ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($op === 'list'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0">Catálogo de Películas</h2>
                    <div>
                        <a href="?op=add" class="btn btn-primary btn-sm me-2">
                            <i class="bi bi-plus"></i> Nueva
                        </a>
                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#backupModal">
                            <i class="bi bi-cloud-arrow-up"></i>
                        </button>
                    </div>
                </div>
                
                <div class="card mb-4 shadow-sm">
                    <div class="card-body p-2">
                        <form method="GET" class="mb-0">
                            <input type="hidden" name="op" value="list">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" class="form-control" placeholder="Buscar películas..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (empty($paginatedMovies)): ?>
                    <div class="alert alert-info">
                        No hay películas <?= empty($search) ? '' : 'con ese término de búsqueda' ?>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
                        <?php foreach ($paginatedMovies as $index => $movie): 
                            $realIndex = array_keys($filteredMovies)[($current_page-1)*ITEMS_PER_PAGE + $index];
                        ?>
                        <div class="col">
                            <div class="card movie-card h-100">
                                <img src="<?= htmlspecialchars($movie['img']) ?>" class="card-img-top movie-poster" alt="<?= htmlspecialchars($movie['titulo']) ?>" onerror="this.src='https://via.placeholder.com/300x450?text=No+Imagen'">
                                <div class="card-body p-3">
                                    <h6 class="movie-title mb-2"><?= htmlspecialchars($movie['titulo']) ?></h6>
                                    <div class="mb-2">
                                        <?php foreach (array_slice($movie['categoria'], 0, 2) as $cat): ?>
                                            <span class="badge bg-secondary genre-badge"><?= htmlspecialchars($cat) ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($movie['categoria']) > 2): ?>
                                            <span class="badge bg-light text-dark genre-badge">+<?= count($movie['categoria']) - 2 ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-white p-2">
                                    <div class="d-flex justify-content-between">
                                        <a href="?op=edit&id=<?= $realIndex ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?op=delete&id=<?= $realIndex ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar esta película?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginación -->
                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?op=list&page=<?= $current_page-1 ?>&search=<?= urlencode($search) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="?op=list&page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?op=list&page=<?= $current_page+1 ?>&search=<?= urlencode($search) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
                
            <?php elseif (in_array($op, ['add', 'edit'])): ?>
                <?php 
                $index = $_GET['id'] ?? null;
                $movie = $index !== null ? ($movies[$index] ?? null) : null;
                ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0"><?= $movie ? 'Editar' : 'Agregar' ?> Película</h2>
                    <a href="?op=list" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST" action="?op=save">
                            <input type="hidden" name="index" value="<?= $index ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Importar desde TMDB</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control form-control-sm" id="tmdb_id" placeholder="ID de TMDB">
                                        <button type="button" id="quickImport" class="btn btn-primary btn-sm">
                                            <i class="bi bi-cloud-download"></i> Importar
                                        </button>
                                    </div>
                                    <small class="text-muted">Ingresa el ID de TheMovieDB</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título</label>
                                <input type="text" class="form-control form-control-sm" id="titulo" name="titulo" required 
                                       value="<?= htmlspecialchars($movie['titulo'] ?? '') ?>">
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="imb" class="form-label">ID de TheMovieDB</label>
                                    <input type="number" class="form-control form-control-sm" id="imb" name="imb" required 
                                           value="<?= htmlspecialchars($movie['imb'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="img" class="form-label">URL de la Imagen</label>
                                    <input type="url" class="form-control form-control-sm" id="img" name="img" required 
                                           value="<?= htmlspecialchars($movie['img'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Categorías</label>
                                <div class="d-flex flex-wrap">
                                    <?php $currentGenres = $movie['categoria'] ?? []; ?>
                                    <?php foreach ($allGenres as $genre): ?>
                                    <div class="form-check me-3 mb-2">
                                        <input class="form-check-input" type="checkbox" name="categoria[]" 
                                               id="cat_<?= str_replace(' ', '_', $genre) ?>" 
                                               value="<?= htmlspecialchars($genre) ?>" 
                                               <?= in_array($genre, $currentGenres) ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="cat_<?= str_replace(' ', '_', $genre) ?>">
                                            <?= htmlspecialchars($genre) ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Servidores</label>
                                <div id="servers-container">
                                    <?php if ($movie && !empty($movie['servidores'])): ?>
                                        <?php foreach ($movie['servidores'] as $i => $server): ?>
                                        <div class="row mb-2 server-row align-items-center">
                                            <div class="col-5">
                                                <input type="text" class="form-control form-control-sm" name="server_title[]" 
                                                       placeholder="Título" 
                                                       value="<?= htmlspecialchars($server['title']) ?>">
                                            </div>
                                            <div class="col-6">
                                                <input type="url" class="form-control form-control-sm" name="server_url[]" 
                                                       placeholder="URL" 
                                                       value="<?= htmlspecialchars($server['url']) ?>">
                                            </div>
                                            <div class="col-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-server">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="row mb-2 server-row align-items-center">
                                            <div class="col-5">
                                                <input type="text" class="form-control form-control-sm" name="server_title[]" 
                                                       placeholder="Título">
                                            </div>
                                            <div class="col-6">
                                                <input type="url" class="form-control form-control-sm" name="server_url[]" 
                                                       placeholder="URL">
                                            </div>
                                            <div class="col-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-server">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="add-server" class="btn btn-sm btn-outline-secondary mt-2">
                                    <i class="bi bi-plus"></i> Agregar Servidor
                                </button>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Película
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Backup Modal -->
    <div class="modal fade" id="backupModal" tabindex="-1" aria-labelledby="backupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="backupModalLabel">Copia de seguridad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-download"></i> Exportar películas</h6>
                        <p class="small text-muted">Descarga un archivo JSON con todas las películas.</p>
                        <a href="?op=export" class="btn btn-primary btn-sm w-100" download>
                            <i class="bi bi-file-earmark-arrow-down"></i> Exportar JSON
                        </a>
                    </div>
                    
                    <div>
                        <h6 class="fw-bold mb-3"><i class="bi bi-upload"></i> Importar películas</h6>
                        <p class="small text-muted">Sube un archivo JSON para restaurar tus películas.</p>
                        <form method="POST" action="?op=import" enctype="multipart/form-data" id="importForm">
                            <div class="mb-3">
                                <input type="file" class="form-control form-control-sm" name="backup_file" id="backupFile" accept=".json" required>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-file-earmark-arrow-up"></i> Importar JSON
                            </button>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        
        // Cerrar sidebar al hacer clic fuera en móviles
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            
            if (window.innerWidth < 992 && sidebar.classList.contains('show') && 
                !sidebar.contains(e.target) && e.target !== toggleBtn) {
                sidebar.classList.remove('show');
            }
        });
        
        // Agregar/eliminar servidores
        document.addEventListener('DOMContentLoaded', function() {
            const addServerBtn = document.getElementById('add-server');
            const serversContainer = document.getElementById('servers-container');
            
            if (addServerBtn && serversContainer) {
                addServerBtn.addEventListener('click', function() {
                    const newServer = `
                        <div class="row mb-2 server-row align-items-center">
                            <div class="col-5">
                                <input type="text" class="form-control form-control-sm" name="server_title[]" placeholder="Título">
                            </div>
                            <div class="col-6">
                                <input type="url" class="form-control form-control-sm" name="server_url[]" placeholder="URL">
                            </div>
                            <div class="col-1">
                                <button type="button" class="btn btn-danger btn-sm remove-server">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    serversContainer.insertAdjacentHTML('beforeend', newServer);
                });
                
                serversContainer.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-server')) {
                        e.target.closest('.server-row').remove();
                    }
                });
            }
            
            // Importación rápida desde TMDB
            document.getElementById('quickImport')?.addEventListener('click', function() {
                const tmdb_id = document.getElementById('tmdb_id').value;
                if (!tmdb_id) {
                    alert('Por favor ingresa un ID de TMDB');
                    return;
                }
                
                fetch(`https://api.themoviedb.org/3/movie/${tmdb_id}?api_key=<?= API_KEY ?>&language=es-MX`)
                    .then(response => {
                        if (!response.ok) throw new Error('Película no encontrada');
                        return response.json();
                    })
                    .then(data => {
                        if (data.title) {
                            document.getElementById('titulo').value = data.title;
                            document.getElementById('imb').value = data.id;
                            document.getElementById('img').value = data.poster_path ? 
                                `https://image.tmdb.org/t/p/w500${data.poster_path}` : '';
                            
                            // Marcar géneros
                            const genres = data.genres.map(g => g.name);
                            document.querySelectorAll('input[name="categoria[]"]').forEach(checkbox => {
                                checkbox.checked = genres.includes(checkbox.value);
                            });
                            
                            alert('Datos importados correctamente. Completa los servidores y guarda.');
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    });
            });
            
            // Validación de importación
            document.getElementById('importForm')?.addEventListener('submit', function(e) {
                const fileInput = document.getElementById('backupFile');
                if (!fileInput.files[0].name.toLowerCase().endsWith('.json')) {
                    e.preventDefault();
                    alert('Por favor selecciona un archivo JSON válido');
                }
            });
        });
    </script>
</body>
</html>