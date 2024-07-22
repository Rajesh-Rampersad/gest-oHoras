<?php

declare(strict_types=1);

require __DIR__ . '/inc/includes.php';

$categorias = new PluginGestaohorasCategory();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($categorias->updateFields($_POST)) {
            Session::addMessageAfterRedirect(__('The category has been successfully updated!', 'gestaohoras'), true, INFO);
            Html::redirect($CFG_GLPI["root_doc"] . "/plugins/gestaohoras/front/form.category.php");
        } else {
            Session::addMessageAfterRedirect(__('Error updating category. Please try again.', 'gestaohoras'), true, ERROR);
        }
    } catch (Exception $e) {
        Session::addMessageAfterRedirect(__('Unexpected error: ', 'gestaohoras') . $e->getMessage(), true, ERROR);
    }
}

if (isset($_GET['categoria_id']) && !empty($_GET['categoria_id'])) {
    try {
        $categoriaId = intval($_GET['categoria_id']);
        $array = $categorias->checkCategoryFlags($categoriaId);
        echo json_encode($array);
    } catch (Exception $e) {
        echo json_encode(['error' => __('Unexpected error: ', 'gestaohoras') . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => __('Invalid category ID.', 'gestaohoras')]);
}