<?php
// Leer la base de datos JSON local
$moviesData = file_get_contents('movies.json');
$movies = json_decode($moviesData, true)['movies'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        /* TUS ESTILOS ORIGINALES SIN MODIFICAR */
        .tabs .tab a:focus, .tabs .tab a:focus.active { background: none; }
        .col img { aspect-ratio: 2 / 3; width: 100%; border-radius: 5px; margin-bottom: 5px; }
        .padDrew { padding: 10px 0; margin: 0; }
        .sidenav li { transition: background ease .2s; }
        .row .col { padding: 0 5px; }
        .each-category { padding: 0 5px; }
        .sidenav li > a { margin: 0 10px; background: #212121; color: rgb(222,215,215); border-radius: 3px; display: flex; align-items: center; gap: 10px; padding: 0 10px; }
        #nav-mobile a.active-sidenav, .sidenav li > a.active-sidenav { background: rgba(60,128,85,.2); color: #20bf6b; }
        #nav-mobile a { font-weight: 600; }
        .buscador { float: right; margin: 0 18px; z-index: 1; }
        @media screen and (min-width: 991px) { nav .brand-logo { margin: 0 10px; } }
        @media only screen and (max-width: 992px) { nav .brand-logo { left: 150px; } }
        .tab a, .sidenav li a { font-weight: 600; }
        img.circle { border-radius: 100%; overflow: hidden; }
        .banner { display: flex; gap: 10px; padding: 10px; background: rgb(20,20,20); }
        .sidenav li { line-height: 0; }
        input[type="text"] { padding: 10px; }
        input[type=text]:not(.browser-default):focus:not([readonly]) { border-bottom: 1px solid #20bf6b; box-shadow: 0 1px 0 0 #20bf6b; }
        .container-x { width: 90%; margin: 0 auto; }
        #busquedas span { display: none; }
        .animation { opacity: 1; animation: fade 2s ease; }
        @keyframes fade { 0% { opacity: 0; } 100% { opacity: 1; }}

        /* SOLO AÑADIDOS PARA TV (se activan en pantallas grandes) */
        @media (min-width: 1200px) {
            /* 6 columnas en TV */
            #busquedas .col.s4 {
                width: 16.666% !important;
                float: left;
                padding: 0 10px;
            }
            
            /* Enfoque visible para control remoto */
            #busquedas a:focus {
                outline: 3px solid #20bf6b !important;
                transform: scale(1.02);
                z-index: 1;
                position: relative;
            }
            
            /* Tamaños aumentados para TV */
            body {
                font-size: 18px;
            }
            
            .movie-item img {
                margin-bottom: 10px;
                transition: transform 0.2s;
            }
            
            .movie-item img:hover {
                transform: scale(1.05);
            }
            
            /* Barra de búsqueda optimizada para TV */
            .search-container {
                padding: 20px 15px;
            }
            
            input[type="text"] {
                font-size: 1.2rem;
                height: 50px;
            }
        }
    </style>
    <title>Buscador - FlixStream</title>
</head>
<body class="grey darken-4">
    <div class="navbar-fixed">
        <nav>
            <div class="nav-wrapper black">
                <div class="container-x">
                    <div class="input-field col s6">
                        <input placeholder="Buscar..." type="text" class="white-text" id="buscar" onkeyup="filterMovies()" tabindex="0">
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <div id="messageContainer" style="display:none; background: #20bf6b; color: white; padding: 10px; position: fixed; top: 10px; right: 10px; z-index: 1000; border-radius: 5px;">
        <span id="messageText"></span>
    </div>

    <ul id="busquedas" class="row padDrew">
        <?php foreach ($movies as $movie): ?>
            <li class="col s4 m3 l2">
                <a href="movie.php?imb=<?= $movie['imb'] ?>" tabindex="0">
                    <img src="<?= $movie['img'] ?>" alt="<?= $movie['titulo'] ?>" loading="lazy">
                    <span><?= $movie['titulo'] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
    // FUNCIONALIDAD ORIGINAL (sin cambios)
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('busquedas').style.display = 'block';
    });

    function showMessage(message) {
        const messageContainer = document.getElementById('messageContainer');
        const messageText = document.getElementById('messageText');
        
        messageText.textContent = message;
        messageContainer.style.display = 'block';

        setTimeout(() => {
            messageContainer.style.display = 'none';
        }, 3000);
    }

    function filterMovies() {
        const input = document.getElementById('buscar');
        const filter = input.value.toLowerCase();
        const ul = document.getElementById('busquedas');
        const li = ul.getElementsByTagName('li');

        for (let i = 0; i < li.length; i++) {
            const a = li[i].getElementsByTagName("a")[0];
            const title = a.getElementsByTagName("span")[0].textContent.toLowerCase();
            if (title.includes(filter)) {
                li[i].style.display = "";
            } else {
                li[i].style.display = "none";
            }
        }
    }
    
    // SOLO AÑADIDO: Navegación por control remoto (se activa solo en TV)
    document.addEventListener('keydown', function(e) {
        // Solo activar en pantallas grandes (TV)
        if (window.innerWidth >= 1200) {
            const focusable = Array.from(document.querySelectorAll('a[href], input, [tabindex="0"]'));
            const current = document.activeElement;
            let index = focusable.indexOf(current);

            if (e.key === 'ArrowRight') {
                e.preventDefault();
                const next = (index + 1) % focusable.length;
                focusable[next].focus();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                const prev = (index - 1 + focusable.length) % focusable.length;
                focusable[prev].focus();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                const itemsPerRow = Math.floor(window.innerWidth / 200); // Aprox. 6 en TV
                const next = Math.min(index + itemsPerRow, focusable.length - 1);
                focusable[next].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const itemsPerRow = Math.floor(window.innerWidth / 200); // Aprox. 6 en TV
                const prev = Math.max(index - itemsPerRow, 0);
                focusable[prev].focus();
            } else if (e.key === 'Enter' && current.tagName === 'A') {
                current.click();
            }
        }
    });
    </script>
</body>
</html>