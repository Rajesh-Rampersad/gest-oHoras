<?php

declare(strict_types=1);

require __DIR__ . '/inc/includes.php';

// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('gestaohoras') || !$plugin->isActivated('gestaohoras')) {
   Html::displayNotFoundError();
   exit;
}

$object = new PluginGestaohorasBalance_Hour();
$input = new PluginGestaohorasBalance_Input();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação básica
    if (isset($_POST['avulso'])) {
        $input->balanceInputAdd($_POST);
    } elseif (isset($_POST['add'])) {
        $_POST['total'] = $_POST['default'];
        $_POST['users_id'] = Session::getLoginUserID();

        $object->check(-1, CREATE, $_POST);

        if ($object->groupExistis($_POST['groups_id'])) {
            echo "<script>alert('Grupo já cadastrado!'); window.location = '{$CFG_GLPI['root_doc']}/plugins/gestaohoras/front/balance_hour.form.php'</script>";
            exit;
        }

        $newid = $object->add($_POST);
        if ($newid) {
            $object->balanceHistoryAdd($newid, $_POST['default']);
        }

        Html::redirect("{$CFG_GLPI['root_doc']}/plugins/gestaohoras/front/balance_hour.form.php?id=$newid");
    } elseif (isset($_POST['update'])) {
        $object->check($_POST['id'], UPDATE);
        $object->update($_POST);
        Html::back();
    } elseif (isset($_POST['purge'])) {
        $DB->delete('glpi_plugin_gestaohoras_balances_historys', ['plugin_gestaohoras_balances_hours_id' => $_POST['id']]);
        $DB->delete('glpi_plugin_gestaohoras_balances_hours', ['id' => $_POST['id']]);
        Html::redirect("{$CFG_GLPI['root_doc']}/plugins/gestaohoras/front/balance_hour.php");
    }
} else {
    if (PluginGestaohorasBalance_Hour::canView()) {
        Html::header('Gestão de horas (Grupo)', $_SERVER['PHP_SELF'], 'admin', 'PluginGestaohorasBalance_Hour');
        $_GET['id'] = isset($_GET['id']) ? intval($_GET['id']) : -1;
        $object->display($_GET);
        Html::footer();
    } else {
        Html::displayRightError();
    }
}