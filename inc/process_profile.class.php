<?php

/**
 * process_profile short summary.
 *
 * process_profile description.
 *
 * @version 1.0
 * @author MoronO
 */
class PluginProcessmakerProcess_Profile extends CommonDBTM
{

   static $rightname = '';

   function can($ID, $right, array &$input = null) {
      switch ($right) {
         case DELETE :
         case PURGE :
            return (Session::haveRight('plugin_processmaker_config', UPDATE));
      }
      return Session::haveRight('plugin_processmaker_config', $right);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      return __('Authorizations', 'processmaker');
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      global $DB;

      $ID = $item->getField('id');

      $canshowentity = Session::haveRight("entity", READ);
      $canedit = Session::haveRight('plugin_processmaker_config', UPDATE);

      $rand=mt_rand();

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='entityprocess_form$rand' id='entityprocess_form$rand' method='post' action='";
         echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='6'>".__('Authorizations', 'processmaker')."</tr>";

         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<input type='hidden' name='plugin_processmaker_processes_id' value='$ID'>";
         Entity::Dropdown( ['entity' => $_SESSION['glpiactiveentities']]);
         echo "</td><td class='center'>".Profile::getTypeName(1)."</td><td>";
         Profile::dropdownUnder(['value' => Profile::getDefault()]);
         echo "</td><td class='center'>".__('Recursive')."</td><td>";
         Dropdown::showYesNo("is_recursive", 0);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      $query = "SELECT DISTINCT gpp.`id` AS linkID,
                       `glpi_profiles`.`id`,
                       `glpi_profiles`.`name`,
                       `gpp`.`is_recursive`,
                       `glpi_entities`.`completename`,
                       `gpp`.`entities_id`
                FROM `". self::getTable() ."` as gpp
                LEFT JOIN `glpi_profiles`
                     ON (`gpp`.`profiles_id` = `glpi_profiles`.`id`)
                LEFT JOIN `glpi_entities`
                     ON (`gpp`.`entities_id` = `glpi_entities`.`id`)
                WHERE `gpp`.`plugin_processmaker_processes_id` = '$ID'
                ORDER BY `glpi_profiles`.`name`, `glpi_entities`.`completename`";
      $result = $DB->query($query);
      $num = $DB->numrows($result);

      echo "<div class='spaced'>";
      Html::openMassiveActionsForm('mass'.__CLASS__.$rand);

      if ($canedit && $num) {
         $massiveactionparams = ['num_displayed' => $num,
                           'container'     => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }

      if ($num > 0) {
         echo "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_begin  .= "<th>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>"._n('Entity', 'Entities', Session::getPluralNumber())."</th>";
         $header_end .= "<th>".sprintf('%1$s (%2$s)', Profile::getTypeName(Session::getPluralNumber()),
                                       __('D=Dynamic, R=Recursive'));
         $header_end .= "</th></tr>";
         echo $header_begin.$header_top.$header_end;

         while ($data = $DB->fetch_assoc($result)) {
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td width='10'>";
               if (in_array($data["entities_id"], $_SESSION['glpiactiveentities'])) {
                  Html::showMassiveActionCheckBox(__CLASS__, $data["linkID"]);
               } else {
                  echo "&nbsp;";
               }
                echo "</td>";
            }
            echo "<td>";

            $link = $data["completename"];
            if ($_SESSION["glpiis_ids_visible"]) {
                $link = sprintf('%1$s (%2$s)', $link, $data["entities_id"]);
            }

            if ($canshowentity) {
                echo "<a href='".Toolbox::getItemTypeFormURL('Entity')."?id=".
                $data["entities_id"]."'>";
            }
            echo $link.($canshowentity ? "</a>" : '');
            echo "</td>";

            if (Profile::canView()) {
                $entname = "<a href='".Toolbox::getItemTypeFormURL('Profile')."?id=".$data["id"]."'>".
                             $data["name"]."</a>";
            } else {
                $entname =  $data["name"];
            }

            if ($data["is_recursive"]) {
                $entname = sprintf('%1$s %2$s', $entname, "<span class='b'>(");
               if ($data["is_recursive"]) {
                  //TRANS: letter 'R' for Recursive
                  $entname = sprintf('%1$s%2$s', $entname, __('R'));
               }
                $entname = sprintf('%1$s%2$s', $entname, ")</span>");
            }
            echo "<td>".$entname."</td>";
            echo "</tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";
      } else {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('No item found')."</th></tr>";
         echo "</table>\n";
      }

      if ($canedit && $num) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
      }
      Html::closeForm();
      echo "</div>";
   }


   /**
    * Summary of prepareInputForAdd
    * @param mixed $input
    * @return mixed
    */
   function prepareInputForAdd($input) {
      $tmp = new self;
      if ($tmp->getFromDBByQuery(" WHERE `plugin_processmaker_processes_id` = ".$input['plugin_processmaker_processes_id']."
                                 AND `entities_id` = ".$input['entities_id']."
                                 AND `profiles_id` = ".$input['profiles_id'])) {
         //// then update existing
         //$tmp->update(['id' => $tmp->getID(),
         //              'is_recursive' => $input['is_recursive']]);
         Session::addMessageAfterRedirect(__('Authorization not added: already existing!', 'processmaker'), true, WARNING);

         return []; // to cancel add
      }
      return $input;
   }

}
