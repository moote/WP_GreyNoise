<?php

/**
 * Render the plugin's admin dashboard
 * 
 * @author  Rich Conaway
 * 
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

namespace WP_GreyNoise;

require_once(WP_GREYNOISE_PLUGIN_DIR.'class-wp-greynoise.php');
require_once(WP_GREYNOISE_PLUGIN_DIR.'class-wp-greynoise-log-table.php');

class WP_GreyNoise_Dashboard
{
    /**
     * Render the dashboard page
     */
    public static function pageRender()
    {
        // int table class
        $wpgLogTable = new WP_Greynoise_Log_Table();
        $wpgLogTable->prepare_items();

        // get counts
        $totalCountObj = $wpgLogTable->getTotalLogCountObj();
        $maliciousCountObj = $wpgLogTable->getMaliciousLogCountObj();

?>
        <div id="wpg-dashboard-wrap" class="wrap">
            <h2>WP GreyNoise Dashboard</h2>

            <?php if (empty(get_option('wpg_api_key'))) : ?>
                <div class="notice notice-error inline">
                    You must enter a valid API key to run WP Greynoise!
                </div>
            <?php elseif (!get_option('wpg_is_enable_greynoise')) : ?>
                <div class="notice notice-warning inline">
                    WP GreyNoise is disabled!
                </div>
            <?php else : ?>
                <div class="notice notice-success inline">
                    WP GreyNoise is running!
                </div>
            <?php endif ?>

            <?php self::summaryBoxRender($totalCountObj, $maliciousCountObj) ?>
            
            <form id="wpg-logs-filter" method="get">
                <input type="hidden" name="page" value="wp_greynoise_dash" />
                <?php $wpgLogTable->search_box('Search IP Address', 'search_id') ?>
                <?php $wpgLogTable->display() ?>
            </form>
        </div>
<?php
    }

    /**
     * Render dashboard summary box
     */
    public static function summaryBoxRender($totalCountObj, $maliciousCountObj)
    {
        // get stats
        $maliciousPerc = self::getMaliciousLogPerc($totalCountObj, $maliciousCountObj);
?>
        <div id="wpg-dash-summary-wrap">
            <div class="card">
                <h2 class="title">Summary</h2>
                <ul>
                    <li class="post-count">
                        Total Logs / Hits: <?php printf('%d / %d', $totalCountObj->lcnt, $totalCountObj->lhit) ?>
                    </li>
                    <li class="page-count">
                        Malicious Logs / Hits: <?php printf('%d / %d', $maliciousCountObj->lcnt, $maliciousCountObj->lhit) ?>
                    </li>
                    <li class="comment-count">
                        Your site traffic is <?php echo $maliciousPerc ?>&percnt; malicious
                    </li>
                </ul>
                <br class="clear" />
            </div>
        </div>
<?php
    }

    /**
     * Get the percentage of all malicious log hits vs. non-malicious.
     * 
     * @return string
     */
    protected static function getMaliciousLogPerc(object $totalLogCntObj, object $maliciousCntObj): string
    {
        // prevent div by zero if no hits
        if($totalLogCntObj->lhit == 0){
            $maliciousPerc = 0;    
        }
        else{
            $maliciousPerc = ($maliciousCntObj->lhit / $totalLogCntObj->lhit) * 100;
        }

        // return formatted string, two decimal places
        return number_format($maliciousPerc, 2);
    }

    /**
     * Check for single log delete request
     */
    public static function deleteLog()
    {
        // check for delete request
        if(
            isset($_REQUEST['page'])
            && $_REQUEST['page'] === 'wp_greynoise_dash'
            && isset($_GET['action'])
            && $_GET['action'] === 'delete'
            && isset($_GET['log_id'])
            && !empty($_GET['log_id'])
        ){
            global $wpdb;
            
            // get table name
            $tableName = WP_GreyNoise::buildTableName();

            // build query
            $query = $wpdb->prepare(
                "
                    DELETE
                    FROM {$tableName}
                    WHERE id = %d
                ",
                [
                    $_GET['log_id'],
                ]
            );

            // execute query
            $wpdb->query($query);

            // reload page & exit
            wp_redirect(
                site_url('/wp-admin/admin.php?page='.$_REQUEST['page'])
            );
            exit();
        }
    }

    /**
     * Check for bulk log delete request
     */
    public static function deleteLogs()
    {
        // hack: the action this is triggered by has not loaded template.php
        // but WP_List_Table::__construct() calls convert_to_screen()
        require_once(ABSPATH.'wp-admin/includes/template.php');

        // use WP_List_Table to get action from top or bottom
        $wpLT = new WP_List_Table();
        
        // check for delete request
        if(
            isset($_REQUEST['page'])
            && $_REQUEST['page'] === 'wp_greynoise_dash'
            && $wpLT->current_action() !== false
            && $wpLT->current_action() === 'delete_selected'
            && isset($_GET['log_ids'])
            && !empty($_GET['log_ids'])
        ){
            global $wpdb;
            
            // get table name
            $tableName = WP_GreyNoise::buildTableName();

            // create list of placeholders for all ids
            $placeholders = array_fill(0, count($_GET['log_ids']), '%s');
            $placeholders = implode(',', $placeholders);

            // build query
            $query = $wpdb->prepare(
                "
                    DELETE
                    FROM {$tableName}
                    WHERE id IN({$placeholders})
                ",
                $_GET['log_ids']
            );

            // execute query
            $wpdb->query($query);

            // reload page & exit
            wp_redirect(
                site_url('/wp-admin/admin.php?page='.$_REQUEST['page'])
            );
            exit();
        }
    }
}
