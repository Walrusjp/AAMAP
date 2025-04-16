<!DOCTYPE html>
<html>
<head>
    <title>Registro de Productos Directos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background-color: rgba(211, 211, 211, 0.4) !important;
            padding-top: 20px;
        }
        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        h2 {
            color: #343a40;
            margin-bottom: 25px;
            text-align: center;
        }
        .form-group label {
            font-weight: 500;
            color: #495057;
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .btn-submit {
            background-color: #28a745;
            border-color: #28a745;
            font-weight: 500;
            padding: 8px 20px;
        }
        .btn-submit:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-secondary {
            padding: 8px 20px;
        }
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            min-width: 300px;
        }
    </style>
    <script>
        // Eliminar el estado POST del historial
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Prevenir recarga con F5 o Ctrl+R (opcional)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
                e.preventDefault();
                setTimeout(function() {
                    window.location.href = window.location.href;
                }, 50);
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="form-container">
                    <h2>Registrar Producto Directo</h2>
                    
                    <!-- Alertas de éxito o error -->
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="codigo">Código:</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción:</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" required></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="um">Unidad de Medida (UM):</label>
                                <input type="text" class="form-control" id="um" name="um" required>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="proceso">Proceso:</label>
                                <select class="form-control" id="proceso" name="proceso" required>
                                    <option value="maq">Maquila</option>
                                    <option value="man">Manufacturado</option>
                                    <option value="com">Comercial</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="precio_unitario">Precio Unitario:</label>
                                <input type="number" class="form-control" id="precio_unitario" name="precio_unitario" step="0.01" required>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="id_cliente">Cliente:</label>
                                <select class="form-control" id="id_cliente" name="id_cliente" required>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?php echo $cliente['id']; ?>">
                                            <?php echo htmlspecialchars($cliente['nombre_comercial']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group text-right">
                            <a href="ver_prod_directos.php" class="btn btn-secondary mr-2">Regresar</a>
                            <button type="submit" name="registrar" class="btn btn-submit">Guardar Producto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts de Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>