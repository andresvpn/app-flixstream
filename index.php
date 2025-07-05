<?php
// Leer el archivo JSON
$moviesData = file_get_contents('movies.json');
$movies = json_decode($moviesData, true)['movies'];

// Invertir el orden del array para mostrar las más recientes primero
$movies = array_reverse($movies);

// Configuración de paginación
$moviesPerPage = 48;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        /* ESTILOS ORIGINALES RESTAURADOS */
        .tabs .tab a:focus, .tabs .tab a:focus.active { background: none; }
        .col img {
            aspect-ratio: 2 / 3;
            width: 100%;
            border-radius: 5px;
            margin-bottom: 10px;
            display: block;
            object-fit: cover;
            height: auto;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .col img.loaded {
            opacity: 1;
        }
        .movie-container {
            aspect-ratio: 2 / 3;
            width: 100%;
            margin-bottom: 10px;
            position: relative;
            overflow: hidden;
            border-radius: 5px;
            background-color: #212121; /* Fondo temporal */
        }
        .padDrew { padding: 10px 0; margin: 50px 0; }
        .sidenav li { transition: background ease .2s; }
        .row .col { padding: 0 5px; }
        .each-category { padding: 0 5px; }
        .sidenav li > a { 
            margin: 0 10px; 
            background: #212121; 
            color: white;
            border-radius: 3px; 
            display: flex; 
            align-items: center; 
            gap: 8px;
            padding: 0 10px;
            height: 44px;
        }
        #nav-mobile a.active-sidenav, .sidenav li > a.active-sidenav { 
            background: rgba(60,128,85,.2); 
            color: white;
        }
        #nav-mobile a { font-weight: 600; }
        .tab a, .sidenav li a { font-weight: 600; }
        img.circle { border-radius: 100%; overflow: hidden; }
        .banner { display: flex; gap: 10px; padding: 10px; background: rgb(20,20,20); }
        .sidenav li { line-height: 0; }
        * { list-style-type: none; }
        .message-container {
            color: red;
            text-align: center;
            padding: 20px;
            font-weight: bold;
        }
        .brand-logo {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.8rem !important;
            margin-left: 15px;
            color: white;
            display: inline-block;
        }
        .brand-logo .green-text {
            color: #20bf6b;
        }
        .sidenav {
            width: 250px;
            background-color: #0f0f0f;
        }
        .sidenav li a {
            color: white !important;
        }
        .sidenav li a:hover {
            background-color: #333 !important;
        }
        .sidenav-trigger {
            margin: 0 15px;
            display: flex !important;
            align-items: center;
            color: white !important;
        }
        #menu-buscador {
            margin: 20px 10px;
            display: flex;
            align-items: center;
            background-color: #20bf6b;
            border-radius: 5px;
            padding: 0 15px;
            text-decoration: none;
            transition: background-color 0.3s;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            height: 44px;
            gap: 8px;
        }
        #menu-buscador:hover {
            background-color: #1a9957 !important;
        }
        #menu-buscador i {
            font-size: 1.2rem;
            color: white;
        }
        .menu-item {
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            padding: 0 10px !important;
            margin: 5px 10px !important;
            height: 44px;
            display: flex !important;
            align-items: center;
            gap: 8px;
        }
        .menu-item i {
            font-size: 1.4rem;
            color: white;
            width: 24px;
            text-align: center;
        }
        .sidenav-header {
            padding: 25px 15px;
            background-color: #0a0a0a;
            border-bottom: 1px solid #333;
            margin-bottom: 15px;
            text-align: center;
        }
        .sidenav-header h4 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            margin: 0;
            color: white;
            width: 100%;
        }
        .sidenav-header h4 .green-text {
            color: #20bf6b;
        }
        .load-more-btn {
            background-color: #20bf6b;
            color: white;
            margin: 20px auto;
            display: block;
            width: 200px;
            text-align: center;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .load-more-btn:hover {
            background-color: #1a9957;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* MEJORAS PARA TV */
        @media (min-width: 1200px) {
            .col.s4 {
                width: 16.666% !important;
                margin-left: 0;
            }
            .row .col {
                padding: 0 8px;
            }
            :focus-visible {
                outline: 3px solid #20bf6b !important;
                transform: scale(1.02);
                z-index: 1;
            }
            a:focus-visible img {
                outline: 3px solid #20bf6b;
                border-radius: 5px;
            }
            .sidenav-trigger:focus-visible {
                background-color: rgba(32, 191, 107, 0.2);
            }
            .sidenav li a:focus-visible {
                background-color: rgba(32, 191, 107, 0.2) !important;
                transform: scale(1.02);
            }
            .movie-container {
                height: 100%;
            }
            .movie-container img {
                height: 100%;
                width: 100%;
                object-fit: cover;
            }
        }

        /* SOLUCIÓN PARA IMÁGENES MOCHADAS */
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            width: 100%;
        }
        
        .genre-section {
            position: relative;
        }
        
        .scroll-target {
            position: absolute;
            top: -100px;
            left: 0;
        }
    </style>
    <title>FlixStream</title>
</head>
<body class="grey darken-4">
    <div id="mainContent">
        <div class="navbar-fixed">
            <nav class="nav-extended black">
                <div class="nav-wrapper">
                    <a href="#" data-target="mobile-demo" class="sidenav-trigger left"><i class="material-icons">menu</i></a>
                    <span class="brand-logo center">Chunito<span class="green-text">Films</span></span>
                    <span class="right hide-on-med-and-down" style="width: 48px; height: 48px;"></span>
                </div>

                <div class="nav-content">
                    <ul class="tabs tabs-transparent" id="genre-tabs">
                        <!-- Pestañas de géneros -->
                    </ul>
                </div>
            </nav>
        </div>

        <ul class="sidenav grey darken-4" id="mobile-demo">
            <li>
                <div class="sidenav-header">
                    <h4>Chunito<span class="green-text">Films</span></h4>
                </div>
            </li>
            <li><a id="menu-buscador" href="/search.php" tabindex="0"><i class="bi bi-search"></i> Buscar Película</a></li>
            <!--
            <li><a id="menu-item" href="/version.php" tabindex="0"><i class="bi bi-info-circle" style="color: white;"></i> Información</a></li> 
            -->      
        </ul>

        <div class="each-category" id="genre-sections">
            <!-- Secciones de películas por género -->
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
        <script>
            // Inicialización del menú lateral
            document.addEventListener('DOMContentLoaded', function() {
                var elems = document.querySelectorAll('.sidenav');
                var instances = M.Sidenav.init(elems, {
                    edge: 'left',
                    draggable: true
                });
                
                let isMenuOpen = false;
                
                window.toggleMenu = function() {
                    if (isMenuOpen) {
                        instances[0].close();
                        setTimeout(() => {
                            document.querySelector('.sidenav-trigger').focus();
                        }, 300);
                    } else {
                        instances[0].open();
                        setTimeout(() => {
                            const firstMenuItem = document.querySelector('.sidenav li a');
                            if (firstMenuItem) firstMenuItem.focus();
                        }, 300);
                    }
                    isMenuOpen = !isMenuOpen;
                };
            });

            // Datos de películas
            const movies = <?php echo json_encode($movies); ?>;
            const moviesPerPage = <?php echo $moviesPerPage; ?>;
            
            // Obtener géneros únicos
            const uniqueGenres = [...new Set(movies.flatMap(movie => movie.categoria))];

            // Referencias del DOM
            const genreTabs = document.querySelector('#genre-tabs');
            const genreSections = document.querySelector('#genre-sections');

            // Estado de paginación
            const paginationState = {};

            // Inicializar paginación
            uniqueGenres.forEach(genre => {
                const genreMovies = movies.filter(movie => movie.categoria.includes(genre));
                paginationState[genre] = {
                    currentPage: 1,
                    totalPages: Math.ceil(genreMovies.length / moviesPerPage),
                    totalMovies: genreMovies.length
                };
            });

            // Manejar carga de imágenes
            function handleImageLoad(img) {
                img.classList.add('loaded');
                // Forzar reflow para evitar problemas de renderizado
                const container = img.closest('.movie-container');
                container.style.display = 'none';
                container.offsetHeight; // Trigger reflow
                container.style.display = 'block';
            }

            // Renderizar películas
            function renderMovies(genre, page = 1) {
                const filteredMovies = movies.filter(movie => movie.categoria.includes(genre));
                const startIndex = (page - 1) * moviesPerPage;
                const endIndex = startIndex + moviesPerPage;
                const paginatedMovies = filteredMovies.slice(startIndex, endIndex);
                
                return paginatedMovies.map(movie => `
                    <div class="col s4">
                        <a href="/movie.php?imb=${movie.imb}" tabindex="0" class="movie-link">
                            <div class="movie-container">
                                <img src="${movie.img}" alt="${movie.titulo}" loading="lazy" 
                                     onload="handleImageLoad(this)" onerror="this.parentElement.style.display='none'">
                            </div>
                        </a>
                    </div>
                `).join('');
            }

            // Crear botón "Cargar más"
            function createLoadMoreButton(genre) {
                if (paginationState[genre].currentPage < paginationState[genre].totalPages) {
                    return `
                        <div class="col s12 center-align">
                            <button class="load-more-btn waves-effect" data-genre="${genre}" tabindex="0">
                                Cargar más (${paginationState[genre].currentPage * moviesPerPage}/${paginationState[genre].totalMovies})
                            </button>
                        </div>
                    `;
                }
                return '';
            }

            // Generar pestañas y secciones
            uniqueGenres.forEach((genre, index) => {
                // Pestañas
                const tab = `<li class="tab"><a href="#${genre}" class="${index === 0 ? 'active' : ''}" data-genre="${genre}" tabindex="0">${genre}</a></li>`;
                genreTabs.innerHTML += tab;

                // Sección de películas
                const section = `
                    <div id="${genre}" class="col s12 padDrew genre-section" style="display: ${index === 0 ? 'block' : 'none'};">
                        <div class="scroll-target" id="target-${genre}"></div>
                        <div class="row">
                            ${renderMovies(genre)}
                        </div>
                        ${createLoadMoreButton(genre)}
                    </div>
                `;
                genreSections.innerHTML += section;
            });

            // Evento para cargar más películas
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('load-more-btn')) {
                    const genre = e.target.getAttribute('data-genre');
                    paginationState[genre].currentPage += 1;
                    
                    const moviesContainer = document.querySelector(`#${genre} .row`);
                    moviesContainer.innerHTML += renderMovies(genre, paginationState[genre].currentPage);
                    
                    const loadMoreContainer = e.target.parentElement;
                    loadMoreContainer.outerHTML = createLoadMoreButton(genre);
                    
                    // Enfocar primer elemento de la nueva página
                    const firstNewItem = moviesContainer.querySelector(`.col.s4:nth-child(${(paginationState[genre].currentPage - 1) * moviesPerPage + 1}) a`);
                    if (firstNewItem) firstNewItem.focus();
                }
            });

            // Cambio de género con scroll al inicio
            const tabs = document.querySelectorAll('.tab a');
            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    const selectedGenre = this.getAttribute('data-genre');
                    
                    // Actualizar pestañas activas
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Mostrar sección correspondiente
                    const sections = document.querySelectorAll('#genre-sections > div');
                    sections.forEach(section => {
                        section.style.display = section.id === selectedGenre ? 'block' : 'none';
                    });

                    // Scroll al punto de referencia
                    const target = document.querySelector(`#target-${selectedGenre}`);
                    if (target) {
                        setTimeout(() => {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }, 50);
                    }

                    // Forzar redibujado de imágenes
                    setTimeout(() => {
                        document.querySelectorAll(`#${selectedGenre} img`).forEach(img => {
                            if (!img.classList.contains('loaded')) {
                                img.style.display = 'none';
                                setTimeout(() => {
                                    img.style.display = 'block';
                                    if (img.complete) handleImageLoad(img);
                                }, 100);
                            }
                        });
                    }, 100);
                });
            });

            // Inicializar tabs
            M.Tabs.init(document.querySelectorAll('.tabs'));

            // Navegación por control remoto
            document.addEventListener('keydown', function(e) {
                const mainFocusableElements = Array.from(document.querySelectorAll(
                    '#mainContent a[href]:not(.sidenav a), #mainContent button:not(.sidenav button), #mainContent [tabindex="0"]:not(.sidenav [tabindex="0"]), .sidenav-trigger'
                )).filter(el => {
                    return el.offsetParent !== null && 
                           getComputedStyle(el).visibility !== 'hidden' && 
                           getComputedStyle(el).display !== 'none' &&
                           !el.closest('.sidenav');
                });
                
                const menuFocusableElements = Array.from(document.querySelectorAll(
                    '.sidenav a[href], .sidenav button, .sidenav [tabindex="0"]'
                )).filter(el => {
                    return el.offsetParent !== null && 
                           getComputedStyle(el).visibility !== 'hidden' && 
                           getComputedStyle(el).display !== 'none';
                });
                
                const currentElement = document.activeElement;
                let currentIndex;
                let focusableElements;
                
                if (currentElement.closest('.sidenav')) {
                    focusableElements = menuFocusableElements;
                    currentIndex = focusableElements.indexOf(currentElement);
                } else {
                    focusableElements = mainFocusableElements;
                    currentIndex = focusableElements.indexOf(currentElement);
                }
                
                switch(e.key) {
                    case 'ArrowRight':
                    case 'ArrowDown':
                        e.preventDefault();
                        if (currentIndex === -1) {
                            focusableElements[0]?.focus();
                        } else {
                            currentIndex = (currentIndex + 1) % focusableElements.length;
                            focusableElements[currentIndex]?.focus();
                        }
                        break;
                        
                    case 'ArrowLeft':
                    case 'ArrowUp':
                        e.preventDefault();
                        if (currentIndex === -1) {
                            focusableElements[focusableElements.length - 1]?.focus();
                        } else {
                            currentIndex = (currentIndex - 1 + focusableElements.length) % focusableElements.length;
                            focusableElements[currentIndex]?.focus();
                        }
                        break;
                        
                    case 'Enter':
                        e.preventDefault();
                        if (currentElement.classList.contains('sidenav-trigger')) {
                            window.toggleMenu();
                        } else if (currentElement.tagName === 'A' || currentElement.tagName === 'BUTTON') {
                            currentElement.click();
                        }
                        break;
                        
                    case 'Backspace':
                    case 'Escape':
                        e.preventDefault();
                        if (document.querySelector('.sidenav-overlay')) {
                            window.toggleMenu();
                        }
                        break;
                }
            });

            // Carga inicial de imágenes
            window.addEventListener('load', function() {
                document.querySelectorAll('.movie-container img').forEach(img => {
                    if (img.complete) {
                        handleImageLoad(img);
                    } else {
                        // Forzar carga si no se ha cargado después de 500ms
                        setTimeout(() => {
                            if (!img.complete) {
                                img.src = img.src; // Reload image
                            }
                        }, 500);
                    }
                });
                
                // Forzar redibujado inicial después de 1 segundo
                setTimeout(() => {
                    const activeSection = document.querySelector('#genre-sections > div[style="display: block;"]');
                    if (activeSection) {
                        activeSection.querySelectorAll('.movie-container img').forEach(img => {
                            if (!img.classList.contains('loaded')) {
                                const container = img.closest('.movie-container');
                                container.style.display = 'none';
                                container.offsetHeight;
                                container.style.display = 'block';
                                img.style.display = 'none';
                                setTimeout(() => {
                                    img.style.display = 'block';
                                    if (img.complete) handleImageLoad(img);
                                }, 50);
                            }
                        });
                    }
                }, 1000);
            });
        </script>
    </div>
</body>
</html>