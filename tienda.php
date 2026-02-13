<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Te Lo Vendo Cuba - Tu hogar en Cuba</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <style>
        /* ============================================
           BANDERA DE CUBA - Fondo Elegante
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg,
                #002590 0%,
                #002590 25%,
                #ffffff 25%,
                #ffffff 50%,
                #002590 50%,
                #002590 75%,
                #ffffff 75%,
                #ffffff 100%) fixed;
            background-size: 200% 200%;
            background-position: 0% 0%;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.05);
            z-index: 0;
            pointer-events: none;
        }
        
        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 0;
            height: 0;
            border-top: 50vh solid transparent;
            border-bottom: 50vh solid transparent;
            border-left: 30vw solid #CF142B;
            z-index: 1;
            opacity: 0.3;
            pointer-events: none;
        }
        
        /* Contenedor principal */
        .main-container {
            position: relative;
            z-index: 10;
            min-height: 100vh;
        }
        
        /* Contenedor para Jicotea */
        #jicotea-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        /* Asegurar que el contenido esté sobre el fondo */
        .content-wrapper {
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body>
    <!-- Contenedor Principal -->
    <div class="main-container">
        <div class="content-wrapper">
            <!-- Contenido principal aquí -->
        </div>
    </div>
    
    <!-- Contenedor para Jicotea -->
    <div id="jicotea-container">
        <!-- Jicotea se cargará aquí -->
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>

