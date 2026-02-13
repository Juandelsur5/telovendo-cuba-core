<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Te Lo Vendo Cuba | Real Estate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ============================================
           FONDO: BANDERA DE CUBA FIJA
           Azul, Blanco, Rojo
           ============================================ */
        body {
            background: linear-gradient(180deg, 
                #002a8f 33.3%, 
                #ffffff 33.3%, 
                #ffffff 66.6%, 
                #cf142b 66.6%) fixed;
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        /* ============================================
           CONTENEDOR: GLASSMORPHISM
           Fondo oscuro semi-transparente con desenfoque
           ============================================ */
        .hero-container {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 30px;
            margin-top: 10vh;
            color: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* ============================================
           JICOTEA: BOTÓN FLOTANTE VERDE
           Con sombra y alerta de confirmación
           ============================================ */
        #jicotea-ia {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            z-index: 1000;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        #jicotea-ia:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
        }
        
        #jicotea-ia:active {
            transform: scale(0.95);
        }
        
        /* Botón cubano */
        .btn-cuban {
            background-color: #cf142b;
            border: none;
            color: white;
            padding: 10px 25px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-cuban:hover {
            background-color: #a01022;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(207, 20, 43, 0.4);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-container {
                margin-top: 5vh;
                padding: 20px;
            }
            
            #jicotea-ia {
                width: 50px;
                height: 50px;
                font-size: 24px;
                bottom: 15px;
                right: 15px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 hero-container text-center shadow-lg">
            <h1 class="display-4 fw-bold">TE LO VENDO CUBA</h1>
            <p class="lead">Plataforma de Bienes Raíces de Alta Velocidad</p>
            <hr class="my-4">
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="¿Qué buscas en La Habana?" aria-label="Buscador">
                <button class="btn btn-cuban" type="button">BUSCAR</button>
            </div>
            <p class="small fw-bold">SISTEMA OPERATIVO PROPIO - BYPASS ASTRA COMPLETO</p>
        </div>
    </div>
</div>

<!-- JICOTEA: Botón flotante verde con alerta de confirmación -->
<div id="jicotea-ia" onclick="alert('Jicotea AI: Ingeniero, sistema en línea. Lista para indexar propiedades.')">🐢</div>

</body>
</html>
