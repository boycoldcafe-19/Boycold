<?php
// POS-side menu item creation. Mirrors admin_data_api.php's `product_create`
// action (same DB writes, same shared helpers in menu_catalog_service.php,
// same content-based image validation), but is gated by the POS session
// (pos_require_employee) instead of the admin session, because POS staff
// (cashier/admin roles logged in through pos/auth) are never logged into
// the admin panel and were getting a silent 401 from admin_data_api.php.
require_once '../auth/guard.php';
pos_start_session();
require_once '../config/db_config.php';
require_once '../../config/menu_catalog_service.php';

header('Content-Type: application/json; charset=utf-8');

function response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function requireValue(array $data, string $key): string
{
    $value = trim((string) ($data[$key] ?? ''));
    if ($value === '') {
        response(['success' => false, 'error' => "$key is required"], 422);
    }
    return $value;
}

// Confirms an active cashier/admin POS session (same guard pos_inventory_api.php
// and pos_cart_api.php already use). Ends the request with 401 JSON on failure.
$employee = pos_require_employee($connect, true);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = $_POST;

// When an upload is bigger than post_max_size, PHP throws away the whole POST body.
// That used to surface as a confusing "product_name is required" - report the real
// reason instead (same check as admin_data_api.php).
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && empty($_POST) && empty($_FILES)
    && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
    && stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data') === 0) {
    response(['success' => false, 'error' => 'The upload is larger than the server allows (post_max_size = ' . ini_get('post_max_size') . '). Use a smaller image.'], 413);
}

try {
    boycold_ensure_menu_category_schema($connect);
    boycold_ensure_product_id_auto_increment($connect);
    boycold_ensure_product_addons_schema($connect);

    switch ($action) {
        case 'product_create':
            $name = requireValue($data, 'product_name');
            $category = boycold_menu_category_slug(requireValue($data, 'category'));
            if ($category === '') {
                response(['success' => false, 'error' => 'Choose a valid category.'], 422);
            }
            boycold_menu_ensure_category($connect, $category);
            $price = max(0, (float) ($data['price'] ?? 0));
            $addons = boycold_menu_normalize_addons($data['addons'] ?? []);
            $milkChoices = boycold_menu_normalize_addons($data['milk_choices'] ?? []);

            // Reads the file's actual bytes (not its name or browser-supplied MIME
            // type) to confirm it is really a PNG, JPG, or WEBP before saving it
            // under uploads/menu/. Throws a RuntimeException (caught below) for
            // anything else, or if it's missing/too large.
            $uploadedImage = boycold_menu_store_uploaded_image($_FILES['image_file'] ?? null);
            $image = $uploadedImage ?? '';
            $available = !empty($data['is_available']) ? 1 : 0;
            $addonsConfigured = 1;
            $milkChoicesConfigured = 1;

            $stmt = $connect->prepare('INSERT INTO products (product_name, description, price, image, category, is_available, addons_configured, milk_choices_configured) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            if (!$stmt) {
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Could not prepare menu item insert: ' . $connect->error], 500);
            }
            $description = '';
            $stmt->bind_param('ssdssiii', $name, $description, $price, $image, $category, $available, $addonsConfigured, $milkChoicesConfigured);

            $saved = false;
            try {
                $saved = $stmt->execute();
            } catch (Throwable $error) {
                $stmt->close();
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Menu item could not be saved: ' . $error->getMessage()], 409);
            }
            if (!$saved) {
                $error = $stmt->error ?: $connect->error;
                $stmt->close();
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Menu item could not be saved: ' . $error], 409);
            }

            $newProductId = (int) $stmt->insert_id;
            $stmt->close();
            if ($newProductId < 1) {
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Menu item was not saved because no database ID was returned.'], 500);
            }

            try {
                boycold_menu_save_product_addons($connect, $newProductId, $addons);
                boycold_menu_save_product_milk_choices($connect, $newProductId, $milkChoices);
            } catch (Throwable $error) {
                $cleanup = $connect->prepare('DELETE FROM products WHERE id = ?');
                if ($cleanup) {
                    $cleanup->bind_param('i', $newProductId);
                    $cleanup->execute();
                    $cleanup->close();
                }
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Menu item add-ons could not be saved: ' . $error->getMessage()], 409);
            }

            response(['success' => true, 'id' => $newProductId, 'product_name' => $name, 'addons' => $addons, 'milk_choices' => $milkChoices]);

        default:
            response(['success' => false, 'error' => 'Unknown action.'], 400);
    }
} catch (Throwable $error) {
    response(['success' => false, 'error' => $error->getMessage()], 500);
}