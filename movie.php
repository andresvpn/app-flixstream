<?php
// Obtener el ID de la película (imb) desde la URL
$imb = $_GET['imb'] ?? null;
// Ruta del archivo
$archivo = 'db.json';

// Crear archivo si no existe
if (!file_exists($archivo)) {
    file_put_contents($archivo, '{}'); // JSON vacío
    chmod($archivo, 0666);
}

// Inicializar variables
$estadisticas = [];

if ($imb !== null) {
    $fp = fopen($archivo, 'c+');
    if (flock($fp, LOCK_EX)) {
        $contenido = stream_get_contents($fp);
        $data = json_decode($contenido, true) ?: [];

        // Si no existe el ID, crear estructura base
        if (!isset($data[$imb])) {
            $data[$imb] = [
                "views" => 0,
                "stars" => 0,
                "likes" => 0,
                "dislikes" => 0,
                "comments" => 0
            ];
        }

        // Aumentar solo las vistas automáticamente
        $data[$imb]["views"]++;

        // Guardar stats actuales para mostrar en HTML si se quiere
        $estadisticas = $data[$imb];

        // Escribir el archivo actualizado
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}
// Leer la base de datos JSON local
$moviesData = file_get_contents('movies.json');
$movies = json_decode($moviesData, true)['movies'];

// Buscar la película en el JSON local por IMB
$currentMovie = null;
foreach ($movies as $movie) {
    if ($movie['imb'] == $imb) {
        $currentMovie = $movie;
        break;
    }
}

// Configurar la API de The Movie DB
$apiKey = '1f098c7d68777348425d008055475b88';
$movieUrl = "https://api.themoviedb.org/3/movie/$imb?api_key=$apiKey&language=es-MX";
$creditsUrl = "https://api.themoviedb.org/3/movie/$imb/credits?api_key=$apiKey";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--Import Google Icon Font-->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        /* [Todos los estilos originales se mantienen exactamente igual] */
        .tabs .tab a:focus, .tabs .tab a:focus.active { background: none; }
        .col img { width: 100%; border-radius: 5px; margin-bottom: 5px; }
        .padDrew { padding: 10px 0; margin: 50px 0; }
        .sidenav li { transition: background ease .2s; }
        .row .col, .each-category { padding: 0 5px; }
        .sidenav li > a { margin: 0 10px; background: #212121; color: rgb(222,215,215); border-radius: 3px; display: flex; align-items: center; gap: 10px; padding: 0 10px; }
        #nav-mobile a.active-sidenav, .sidenav li > a.active-sidenav { background: rgba(60,128,85,.2); color: #20bf6b; }
        #nav-mobile a { font-weight: 600; }
        .buscador { float: right; margin: 0 18px; z-index: 1; }
        @media screen and (min-width: 991px) { nav .brand-logo { margin: 0 10px; } }
        @media only screen and (max-width: 992px) { nav .brand-logo { left: 120px; } }
        .tab a, .sidenav li a { font-weight: 600; }
        .banner { display: flex; gap: 10px; padding: 10px; background: rgb(20, 20, 20); }
        .sidenav li { line-height: 0; }
        .bg-fondo { 
            display: flex; 
            align-items: flex-end; 
            padding: 10px; 
            padding-top: 50px;
            position: relative; 
            background-size: cover;
            background-position: center;
            min-height: 200px;
        }
        .bg-fondo h4 { font-weight: 500; font-size: 1.5rem; }
        .collection { border: none; border-radius: 0; margin: 0; }
        .collection .collection-item { border-bottom: 2px solid rgba(17, 17, 17, .4); }
        .modal { margin: 50px auto; }
        .message { color: red; font-weight: bold; text-align: center; }
        .cast-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 20px 0;
        }
        .cast-row {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .cast-member {
            text-align: center;
            width: 80px;
        }
        .cast-img-container {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 2px solid #20bf6b;
            padding: 2px;
            margin: 0 auto 5px;
            overflow: hidden;
        }
        .cast-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .cast-name {
            font-size: 11px;
            color: white;
            font-weight: 500;
            text-align: center;
            max-width: 80px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .synopsis-container {
            position: relative;
            margin-bottom: 20px;
        }
        .synopsis-text {
            line-height: 1.6;
        }
        .synopsis-collapsed {
            max-height: 120px;
            overflow: hidden;
            position: relative;
        }
        .synopsis-collapsed::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to bottom, transparent, #1a1a1a);
        }
        .read-more-btn {
            color: #20bf6b;
            cursor: pointer;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
            -webkit-tap-highlight-color: transparent;
        }
        .poster-container {
            width: 100px;
            margin-bottom: 10px;
        }
        .poster-img {
            width: 100%;
            border-radius: 5px;
        }
        .focusable {
            outline: none;
            transition: all 0.2s ease;
        }
        .focused {
            transform: scale(1.05);
            box-shadow: 0 0 0 3px #20bf6b;
            z-index: 10;
        }
        .server-item.focused {
            background-color: rgba(32, 191, 107, 0.2) !important;
        }
        .modal {
            width: 80%;
            max-height: 80%;
        }
        .modal-content {
            padding: 24px;
        }
        .servers-scroll {
            max-height: 60vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="grey darken-4">

<div class="fixed-action-btn">
    <a href="#modal1" class="btn-floating btn-large green darken-2 modal-trigger focusable" id="play-button">
        <i class="large material-icons">play_arrow</i>
    </a>
</div>

<div id="messageContainer" class="message"></div>

<div class="row bg-fondo" id="mainContent">
    <div class="col s3 m4 l2 poster-container">
        <img id="movie-poster" src="" alt="poster" class="poster-img">
    </div>
    <div class="col s9 m8 l10">
        <h4 id="movie-title" class="white-text"></h4>
    </div>
</div>

<div class="container white-text">
    <h5>SINOPSIS</h5>
    <div class="synopsis-container">
        <div id="movie-synopsis-full" class="synopsis-text" style="display: none;"></div>
        <div id="movie-synopsis-short" class="synopsis-text synopsis-collapsed"></div>
        <a id="read-more-btn" class="read-more-btn focusable" tabindex="0">Leer más</a>
    </div>
    
    <div class="cast-section">
        <div class="cast-row" id="cast-row-1"></div>
        <div class="cast-row" id="cast-row-2"></div>
    </div>
</div>

<div id="modal1" class="modal grey darken-4">
    <div class="modal-content white-text">
        <h5>Selecciona servidor</h5>
        <div class="servers-scroll">
            <div class="collection" id="servers-container"></div>
        </div>
    </div>
    <div class="modal-footer grey darken-4">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat white-text focusable" tabindex="0">Cerrar</a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script>
// Variables para control de navegación
let currentFocus = null;
let focusableElements = [];
let currentServerIndex = -1;
let modalInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    M.AutoInit();
    document.getElementById('mainContent').style.display = 'block';
    
    // Inicializar modal
    modalInstance = M.Modal.init(document.getElementById('modal1'), {
        onOpenEnd: function() {
            setTimeout(() => {
                const servers = document.querySelectorAll('.server-item');
                if (servers.length > 0) {
                    setFocus(servers[0]);
                    currentServerIndex = 0;
                }
            }, 100);
        },
        onCloseEnd: function() {
            setFocus(document.getElementById('play-button'));
        }
    });
    
    // Configurar "Leer más" para móviles y control remoto
    const readMoreBtn = document.getElementById('read-more-btn');
    if (readMoreBtn) {
        readMoreBtn.addEventListener('click', toggleSynopsis);
        readMoreBtn.addEventListener('touchend', function(e) {
            e.preventDefault();
            toggleSynopsis();
        });
    }
    
    loadServers();
    getMovieData();
    initTVNavigation();
});

function initTVNavigation() {
    updateFocusableElements();
    document.addEventListener('keydown', handleKeyDown);
    if (focusableElements.length > 0) {
        setFocus(focusableElements[0]);
    }
}

function updateFocusableElements() {
    focusableElements = Array.from(document.querySelectorAll(
        '#play-button, #read-more-btn, .server-item, .modal-close'
    ));
    focusableElements.forEach(el => el.setAttribute('tabindex', '0'));
}

function handleKeyDown(e) {
    if (!currentFocus) return;
    
    const currentIndex = focusableElements.indexOf(currentFocus);
    let nextIndex = currentIndex;
    let isInModal = document.querySelector('.modal.open');
    
    switch(e.key) {
        case 'ArrowUp':
            if (isInModal) {
                if (currentFocus.classList.contains('modal-close')) {
                    const servers = document.querySelectorAll('.server-item');
                    if (servers.length > 0) {
                        nextIndex = focusableElements.indexOf(servers[servers.length - 1]);
                    }
                } else {
                    nextIndex = Math.max(0, currentIndex - 1);
                }
            }
            break;
            
        case 'ArrowDown':
            if (isInModal) {
                if (currentFocus.classList.contains('server-item')) {
                    const servers = document.querySelectorAll('.server-item');
                    const lastServerIndex = focusableElements.indexOf(servers[servers.length - 1]);
                    
                    if (currentIndex >= lastServerIndex) {
                        nextIndex = focusableElements.indexOf(document.querySelector('.modal-close'));
                    } else {
                        nextIndex = currentIndex + 1;
                    }
                }
            } else {
                if (currentFocus.id === 'play-button') {
                    nextIndex = focusableElements.indexOf(document.getElementById('read-more-btn'));
                }
            }
            break;
            
        case 'Enter':
            if (currentFocus.id === 'read-more-btn') {
                toggleSynopsis();
            } else if (currentFocus.classList.contains('server-item')) {
                window.location.href = currentFocus.href;
            } else if (currentFocus.classList.contains('modal-close')) {
                modalInstance.close();
            } else if (currentFocus.id === 'play-button') {
                modalInstance.open();
            }
            break;
            
        case 'Backspace':
            if (isInModal) {
                modalInstance.close();
            }
            break;
    }
    
    if (nextIndex !== currentIndex && nextIndex >= 0 && nextIndex < focusableElements.length) {
        e.preventDefault();
        setFocus(focusableElements[nextIndex]);
    }
}

function setFocus(element) {
    if (!element) return;
    if (currentFocus) currentFocus.classList.remove('focused');
    currentFocus = element;
    currentFocus.classList.add('focused');
    currentFocus.focus();
    
    if (element.classList.contains('server-item')) {
        const container = document.querySelector('.servers-scroll');
        const elementRect = element.getBoundingClientRect();
        const containerRect = container.getBoundingClientRect();
        
        if (elementRect.top < containerRect.top) {
            container.scrollTop += elementRect.top - containerRect.top - 20;
        } else if (elementRect.bottom > containerRect.bottom) {
            container.scrollTop += elementRect.bottom - containerRect.bottom + 20;
        }
    }
}

function toggleSynopsis() {
    const fullEl = document.getElementById('movie-synopsis-full');
    const shortEl = document.getElementById('movie-synopsis-short');
    const btn = document.getElementById('read-more-btn');
    
    const isExpanded = fullEl.style.display === 'block';
    fullEl.style.display = isExpanded ? 'none' : 'block';
    shortEl.style.display = isExpanded ? 'block' : 'none';
    btn.textContent = isExpanded ? 'Leer más' : 'Leer menos';
}

function loadServers() {
    const serversContainer = document.getElementById('servers-container');
    serversContainer.innerHTML = '';
    
    const localMovieData = <?php echo json_encode($currentMovie); ?>;
    
    if (localMovieData?.servidores?.length > 0) {
        localMovieData.servidores.forEach((server, index) => {
            const serverElement = document.createElement('a');
            serverElement.href = "/servidor.html?v=" + server.url;
            serverElement.className = 'collection-item grey darken-4 white-text flow-text waves-effect waves-light focusable server-item';
            serverElement.textContent = server.title;
            serverElement.setAttribute('tabindex', '0');
            serverElement.dataset.index = index;
            serverElement.addEventListener('focus', () => currentServerIndex = index);
            serversContainer.appendChild(serverElement);
        });
    } else {
        serversContainer.innerHTML = '<a class="collection-item grey darken-4 white-text flow-text focusable">No hay servidores disponibles</a>';
    }
    
    updateFocusableElements();
}

async function getMovieData() {
    try {
        const [movieResponse, creditsResponse] = await Promise.all([
            fetch('<?= $movieUrl ?>'),
            fetch('<?= $creditsUrl ?>')
        ]);
        
        const movieData = await movieResponse.json();
        const creditsData = await creditsResponse.json();
        
        if (movieData?.title) {
            document.getElementById('movie-title').textContent = movieData.title;
            setupSynopsis(movieData.overview || 'Sin sinopsis disponible');
            setupImages(movieData);
            setupCast(creditsData.cast?.slice(0, 6) || []);
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('messageContainer').textContent = 'Error al cargar los datos.';
    }
}

function setupSynopsis(synopsis) {
    const fullEl = document.getElementById('movie-synopsis-full');
    const shortEl = document.getElementById('movie-synopsis-short');
    const btn = document.getElementById('read-more-btn');
    
    fullEl.textContent = synopsis;
    fullEl.style.display = 'none';
    
    if (synopsis.length > 200) {
        shortEl.textContent = synopsis.substring(0, 200) + '...';
        btn.style.display = 'inline-block';
        
        // Permitir tocar en el texto para expandir en móviles
        shortEl.style.cursor = 'pointer';
        shortEl.addEventListener('click', toggleSynopsis);
    } else {
        shortEl.textContent = synopsis;
        btn.style.display = 'none';
    }
}

function setupImages(movieData) {
    const poster = document.getElementById('movie-poster');
    const bg = document.querySelector('.bg-fondo');
    
    poster.src = movieData.poster_path 
        ? `https://image.tmdb.org/t/p/w200${movieData.poster_path}`
        : 'https://placehold.co/200x300?text=No+Poster';
    
    if (movieData.backdrop_path) {
        bg.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url(https://image.tmdb.org/t/p/w1280${movieData.backdrop_path})`;
    } else {
        bg.style.backgroundImage = 'linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url(https://placehold.co/1280x720?text=No+Background)';
    }
}

function setupCast(cast) {
    const row1 = document.getElementById('cast-row-1');
    const row2 = document.getElementById('cast-row-2');
    row1.innerHTML = '';
    row2.innerHTML = '';
    
    cast.forEach((person, index) => {
        const member = document.createElement('div');
        member.className = 'cast-member';
        
        const imgContainer = document.createElement('div');
        imgContainer.className = 'cast-img-container';
        
        const img = document.createElement('img');
        img.className = 'cast-img';
        img.loading = 'lazy';
        img.src = person.profile_path 
            ? `https://image.tmdb.org/t/p/w185${person.profile_path}`
            : `https://placehold.co/185x185?text=${person.name.split(' ')[0].charAt(0)}${person.name.split(' ')[1]?.charAt(0) || ''}`;
        img.alt = person.name;
        
        const name = document.createElement('div');
        name.className = 'cast-name';
        name.textContent = person.name;
        
        imgContainer.appendChild(img);
        member.appendChild(imgContainer);
        member.appendChild(name);
        
        if (index < 3) row1.appendChild(member);
        else row2.appendChild(member);
    });
}
</script>
</body>
</html>
