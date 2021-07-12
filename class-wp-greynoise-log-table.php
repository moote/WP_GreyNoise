<?php

namespace WP_GreyNoise;

require_once(WP_GREYNOISE_PLUGIN_DIR.'class-wp-list-table.php');

class WP_Greynoise_Log_Table extends \WP_GreyNoise\WP_List_Table
{
    /** @var object */
    protected $wpdb;

    /** @var object */
    protected $totalLogCountObj;
    
    /** @var int */
    protected $maliciousLogCountObj;
    
    /** @var int */
    protected $totalPages;
    
    /** @var int */
    protected $currentPage;
    
    /** @var array */
    protected $logs;

    const LOGS_PER_PAGE = 5;

    /**
     * Constructor; 
     */
    public function __construct()
    {
        parent::__construct([
            'plural'   => 'logs',
            'singular' => 'log',
            'ajax'     => false,
            'screen'   => null,
        ]);

        // set ref to wpdb
        global $wpdb;
        $this->wpdb = $wpdb;

        // set counts
        $this->getTotalLogCountObj();
        $this->getMaliciousLogCountObj();

        // set pages
        $this->setPages();

        // get logs
        $this->getLogs();
    }

    public function get_columns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'ip_address' => 'IP Address',
            'is_proxy' => 'Is Proxy',
            'seen' => 'Seen',
            'classification' => 'Class',
            'cve' => 'CVE',
            'country' => 'Country',
            'org' => 'Org',
            'hits' => 'Hits',
            'created_at' => 'First Visit',
            'updated_at' => 'Last Visit',
        ];
        return $columns;
    }

    /**
     * Specific column render function for bulk action checkbox column.
     */
    protected function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="log_ids[]" value="%s" />',
            $item->id
        );
    }

    /**
     * Specific column render function for 'ip_address' column. Adds delete
     * action link.
     */
    protected function column_ip_address($item)
    {
        $actions = [
            'delete' => sprintf(
                '<a href="?page=%s&action=%s&log_id=%s" onclick="return confirmDelete()">delete</a>',
                $_REQUEST['page'],
                'delete',
                $item->id
            ),
        ];

        return sprintf('%1$s %2$s', $item->ip_address, $this->row_actions($actions));
    }

    /**
     * Specific column render function for 'cve' column. Lists cves, linking
     * them to Mitre CVE database for easy reference.
     */
    protected function column_cve($item)
    {
        if($item->cve == ''){
            return $item->cve;
        }
        else{
            // get array of cves
            $cveArr = explode(',', $item->cve);
            $cveCount = count($cveArr);
            $cveFormatted = "";
            
            // loop array and list cves; wrap them in link to CVE db
            foreach($cveArr as $cve){
                $cveFormatted .= sprintf('<li><a href="https://cve.mitre.org/cgi-bin/cvename.cgi?name=%s" target="_blank">%s</a></li>', $cve, $cve);
            }

            // gen html
            $cveHtml = <<<EOF
<div>
    {$cveCount} CVEs found
    <br>
    <a id="wpg-cve-show" class="show-hidden-link" href="#" onclick="showCveList()">Show</a>
    <div id="wpg-cve-show-hidden" class="hidden">
        <a id="wpg-cve-show" class="show-hidden-link" href="#" onclick="hideCveList()">Hide</a>
        <ul>
            {$cveFormatted}    
        </ul>
        <br/>
    </div>
</div>
EOF;
            return $cveHtml;
        }
    }

    /**
     * Default column rendering function
     */
    protected function column_default($item, $column_name)
    {
        return $item->$column_name;
    }

    /**
     * Define bulk actions
     */
    protected function get_bulk_actions() {
        $actions = array(
          'delete_selected'    => 'Delete Selected'
        );

        return $actions;
      }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $this->set_pagination_args([
            'total_items' => $this->getTotalLogCountObj()->lcnt,
            'per_page' => self::LOGS_PER_PAGE,
        ]);

        $this->items = $this->logs;
    }

    /**
     * Get the total count of all log records, and the sum
     * of all hits.
     * 
     * @return object
     */
    public function getTotalLogCountObj(): ?object
    {
        if(!$this->totalLogCountObj){
            // execute query
            $this->totalLogCountObj = $this->wpdb->get_row($this->getTotalLogCountQuery());
        }

        return $this->totalLogCountObj;
    }

    /**
     * Get the count of all malicious log records, and the sum
     * of all hits.
     * 
     * @return object
     */
    public function getMaliciousLogCountObj(): ?object
    {
        if(!$this->maliciousLogCountObj){
            // build query
            $query = $this->getTotalLogCountQuery()." WHERE classification = 'malicious'";

            // execute query
            $this->maliciousLogCountObj = $this->wpdb->get_row($query);
        }

        return $this->maliciousLogCountObj;
    }

    /**
     * Prepare the query for total log ccunt
     */
    protected function getTotalLogCountQuery(): string
    {
        // get table name
        $tableName = WP_GreyNoise::buildTableName();

        // build query
        $query = $this->wpdb->prepare("
            SELECT
                COUNT(id) as lcnt,
                SUM(hits) as lhit
            FROM {$tableName}");
        
        return $query;
    }

    /**
     * Set the vaious page variables
     */
    protected function setPages()
    {
        // calc max pages
        $this->totalPages = ceil($this->getTotalLogCountObj()->lcnt / self::LOGS_PER_PAGE);

        // set current page
        if(isset($_GET['paged']) && $_GET['paged'] > 1 && $_GET['paged'] <= $this->totalPages){
            $this->currentPage = $_GET['paged'];
        }
        else{
            $this->currentPage = 1;
        }
    }

    /**
     * Query db for specific page and save to $this->logs
     */
    protected function getLogs()
    {
        // get table name
        $tableName = WP_GreyNoise::buildTableName();

        // build query; test for search string
        if(isset($_GET['s']) && !empty($_GET['s'])){
            $query = $this->wpdb->prepare(
                "
                    SELECT
                        *
                    FROM {$tableName}
                    WHERE
                        ip_address LIKE '%s'
                    ORDER BY updated_at DESC
                    LIMIT %d
                    OFFSET %d
                ",
                [
                    '%'.$this->wpdb->esc_like($_GET['s']).'%',
                    self::LOGS_PER_PAGE,
                    ($this->currentPage - 1) * self::LOGS_PER_PAGE,
                ]
            );
        }
        else{
            $query = $this->wpdb->prepare(
                "
                    SELECT
                        *
                    FROM {$tableName}
                    ORDER BY updated_at DESC
                    LIMIT %d
                    OFFSET %d
                ",
                [
                    self::LOGS_PER_PAGE,
                    ($this->currentPage - 1) * self::LOGS_PER_PAGE,
                ]
            );
        }

        // var_dump($query); exit;

        // execute query
        $this->logs = $this->wpdb->get_results($query);
    }
}
