<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginGestaohorasInstall
{
   protected $migration;

   /**
    * Install the plugin
    * @param Migration $migration
    * @return bool
    */
   public function install(Migration $migration)
   {
      $this->migration = $migration;
      try {
         $this->installSchema();
         $this->createDefaultDisplayPreferences();
         $this->createCronTasks();
         return true;
      } catch (Exception $e) {
         $this->migration->displayWarning("Error during installation: " . $e->getMessage(), true);
         return false;
      }
   }

   /**
    * Uninstall plugin
    * @return bool
    */
   public function uninstall()
   {
      try {
         $this->deleteTables();
         return true;
      } catch (Exception $e) {
         $this->migration->displayWarning("Error during uninstallation: " . $e->getMessage(), true);
         return false;
      }
   }

   /**
    * Create tables in database
    * @throws Exception
    */
   protected function installSchema()
   {
      global $DB;

      $this->migration->displayMessage("Creating database schema");

      $dbFile = __DIR__ . '/mysql/plugin_gestaohoras_empty.sql';

      if (!$DB->runFile($dbFile)) {
         throw new Exception("Error creating tables: " . $DB->error());
      }
   }

   /**
    * Cleanups the database from plugin's itemtypes (tables and relations)
    * @throws Exception
    */
   protected function deleteTables()
   {
      global $DB;

      // Delete display preferences
      $displayPreference = new DisplayPreference();
      if (!$displayPreference->deleteByCriteria(['itemtype' => 'PluginGestaohorasBalance_Hour'])) {
         throw new Exception("Error deleting display preferences");
      }

      // Drop tables
      $tables = [
         'glpi_plugin_gestaohoras_balances_historys',
         'glpi_plugin_gestaohoras_balances_hours',
         'glpi_plugin_gestaohoras_itilcategorycategorias',
      ];

      foreach ($tables as $table) {
         if (!$DB->query("DROP TABLE IF EXISTS $table")) {
            throw new Exception("Error dropping table $table: " . $DB->error());
         }
      }
   }

   /**
    * Create display preferences
    * @throws Exception
    */
   protected function createDefaultDisplayPreferences()
   {
      global $DB;

      $this->migration->displayMessage("Creating default display preferences");

      // Create standard display preferences
      $displayprefs = new DisplayPreference();
      $found_dprefs = $displayprefs->find("`itemtype` = 'PluginGestaohorasBalance_Hour'");

      if (count($found_dprefs) == 0) {
         $query = "INSERT IGNORE INTO `glpi_displaypreferences`
                   (`id`, `itemtype`, `num`, `rank`, `users_id`) VALUES
                   (NULL, 'PluginGestaohorasBalance_Hour', 2, 1, 0),
                   (NULL, 'PluginGestaohorasBalance_Hour', 3, 2, 0)";

         if (!$DB->query($query)) {
            throw new Exception("Error inserting display preferences: " . $DB->error());
         }
      }
   }

   /**
    * Create cron tasks
    * @throws Exception
    */
   protected function createCronTasks()
   {
      try {
         // Debitar saldos
         CronTask::Register(PluginGestaohorasBalance_Hour::class, 'DebitoDeHoras', MINUTE_TIMESTAMP,
            [
               'comment'   => 'Gestão de Horas - Efetua os débitos de horas dos grupos requerentes',
               'mode'      => CronTask::MODE_EXTERNAL
            ]
         );

         // Recarrega os saldos dos grupos de acordo com o saldo padrão
         CronTask::Register(PluginGestaohorasJob::class, 'RecarregarSaldos', MONTH_TIMESTAMP,
            [
               'comment'   => 'Recarrega os saldos dos grupos de acordo com o saldo padrão',
               'mode'      => CronTask::MODE_EXTERNAL
            ]
         );
      } catch (Exception $e) {
         throw new Exception("Error creating cron tasks: " . $e->getMessage());
      }
   }
}