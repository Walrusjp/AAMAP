<?php
include 'db_connect.php';

$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

$query = "SELECT id, imagen, descripcion, stock FROM productos WHERE activo = 1 AND (id LIKE ? OR descripcion LIKE ?)";
$search_param = "%" . $search . "%";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <img src="<?php echo htmlspecialchars($row['imagen']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['descripcion']); ?>" style="height:200px; object-fit:cover;">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($row['descripcion']); ?></h5>
                <p class="card-text">
                    <strong>ID:</strong> <?php echo htmlspecialchars($row['id']); ?><br>
                    <strong>Stock:</strong> <?php echo htmlspecialchars($row['stock']); ?>
                </p>
                <div class="d-flex justify-content-between align-items-center">
                    <div style="display:inline-flex;">
                        <button type="button" class="btn btn-success add-to-cart btncard" 
                            data-id="<?php echo $row['id']; ?>"
                            <?php echo ($row['stock'] <= 0) ? 'disabled' : ''; ?>>+</button>
                        <button type="button" class="btn btn-danger remove-from-cart btncard" 
                            data-id="<?php echo $row['id']; ?>">-</button>
                        <?php if ($row['stock'] == 0): ?>
                            <form method="post" action="" onsubmit="return confirm('¿Estás seguro de que deseas solicitar este producto?');">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="request_product" class="btn btn-warning btncard">Solicitar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <span class="badge badge-secondary" id="cart-quantity-<?php echo $row['id']; ?>">
                        <?php echo isset($_SESSION['cart'][$row['id']]) ? $_SESSION['cart'][$row['id']] : 0; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
<?php endwhile; ?>