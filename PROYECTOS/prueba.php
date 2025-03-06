<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

$cod_fab_nuevo = '20250305-1';

// Verificar si el cod_fab ya existe en proyectos
$sql_verificar_proyectos = "SELECT cod_fab FROM proyectos WHERE cod_fab = '$cod_fab_nuevo'";
$result_proyectos = $conn->query($sql_verificar_proyectos);

if ($result_proyectos->num_rows == 0) {
    // El cod_fab no existe en proyectos, insertarlo primero
    $sql_insertar_proyecto = "INSERT INTO proyectos (cod_fab) VALUES ('$cod_fab_nuevo')";
    if (!$conn->query($sql_insertar_proyecto)) {
        throw new Exception("Error al insertar en proyectos: " . $conn->error);
    }
}

// Iniciar una transacción
$conn->begin_transaction();

try {
    // Actualizar proyectos
    $sql_actualizar_proyectos = "UPDATE proyectos SET cod_fab = '$cod_fab_nuevo' WHERE cod_fab = '20250306'";
    if (!$conn->query($sql_actualizar_proyectos)) {
        throw new Exception("Error al actualizar proyectos: " . $conn->error);
    }

    // Actualizar datos_vigencia
    $sql_actualizar_datos_vigencia = "UPDATE datos_vigencia SET cod_fab = '$cod_fab_nuevo' WHERE id = 10";
    if (!$conn->query($sql_actualizar_datos_vigencia)) {
        throw new Exception("Error al actualizar datos_vigencia: " . $conn->error);
    }

    // Confirmar la transacción
    $conn->commit();
    echo "Ambas actualizaciones se realizaron correctamente.";
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>