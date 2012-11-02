<?php

class PerchFieldType_tabular extends PerchAPI_FieldType
{
    /*
     * Define keys for our data store.
     */

    const TITLES_KEY = 'titles';
    const DATA_KEY = 'data';

    /*
     * This variable is used to ensure that our custom CSS is only inserted once.
     */

    private static $page_resources_added = false;

    /*
     * This variable tells Perch that the output of get_processed() is HTML.
     */

    public $processed_output_is_markup = true;

    /*
     * Generate ids for table cells based on the table id, cell row and cell column.
     */

    private function get_cellid($id, $row, $col)
    {
        return sprintf('%s_row%d_%d', $id, $row, $col);
    }

    /*
     * Output an error string to the admin panel.
     */

    private function error_string($string)
    {
        return '<div class="error" style="clear: both; padding: 10px; margin: 12px 0;">'.$string.'</div>';
    }

    /*
     * Function: render_inputs()
     *
     * Purpose: Generates the admin panel HTML for the table.
     *
     * Returns: An HTML string.
     *
     * $details contains metadata for the current region, including
     * the table's data if it has been saved previously.
     */

    public function render_inputs($details=array())
    {
        $data = array();

        /*
         * Retrieve the ids for the table.
         *
         * id is the id specified in the Perch template. Previously saved table
         * data will be stored in the $details array under this key.
         *
         * input_id is the Perch-generated id (prefixed with perch_XXX_).
         * The ids of any HTML elements created here should be based on this
         * id to maintain uniqueness in multi-item regions.
         */

        $id = $this->Tag->id();
        $input_id = $this->Tag->input_id();

        /*
         * Extract the table dimensions from the supplied template tags.
         */

        $rows = $this->Tag->rows() ? intval(trim($this->Tag->rows())) : 0;
        $titles = $this->Tag->cols() ? explode(',', $this->Tag->cols()) : array();
        $cols = PerchUtil::count($titles);

        /*
         * Paranoid check of the table dimensions.
         */

        if ($rows <= 0 || $cols <= 0) {
            return $this->error_string(sprintf('Table has invalid number of rows (%d) or columns (%d). Check your template.', $rows, $cols));
        }

        /*
         * Retrieve previously stored data for the table. This is
         * used to pre-populate the table in the admin panel.
         */

        if (isset($details[$id]) && is_array($details[$id])) {
            if (isset($details[$id][self::DATA_KEY]) && is_array($details[$id][self::DATA_KEY])) {
                $data = $details[$id][self::DATA_KEY];
            }
        }

        /*
         * Generate the table HTML, pre-populating it with data from $details.
         * Displayed table dimensions are strictly as defined in the template.
         * Any additional data stored for the table is ignored and will be discarded
         * upon saving.
         */

        $datarows = PerchUtil::count($data);

        $s = '<table id="'.$input_id.'" class="tabular" cellspacing="0" cellpadding="0"><thead><tr>';

        foreach ($titles as $title) {
            $s .= '<th>'.trim($title).'</th>';
        }
        $s .= '</tr></thead><tbody>';

        for ($row = 0; $row < $rows; $row++) {

            $rowdata = ($row < $datarows) ? $data[$row] : array();
            $datacols = PerchUtil::count($rowdata);

            $s .= '<tr>';
            for ($col = 0; $col < $cols; $col++) {

                $val = ($col < $datacols) ? trim($rowdata[$col]) : '';

                $s .= '<td>'.$this->Form->text($this->get_cellid($input_id, $row, $col), $val).'</td>';
            }
            $s .= '</tr>';
        }

        $s .= '</tbody></table>';

        return $s;
    }

    /*
     * Function: get_raw()
     *
     * Purpose: Assembles the raw data for the table, using the values
     * entered in the admin panel.
     *
     * Returns: An array of raw, non-processed data.
     *
     * $post contains the $_POST fields from the admin form after
     * the user has pressed 'Submit'.
     *
     * $Item is a PerchContentItem object. Not sure what this
     * is for but it's not used here.
     *
     * TODO: Find out what $Item is for.
     */

    public function get_raw($post=false, $Item=false)
    {
        /*
         * Initialise our raw data.
         */

        $store = array(
            self::TITLES_KEY => array(),
            self::DATA_KEY => array()
        );

        /*
         * Added as a precaution. In my tests $post is always populated
         * but most of the built-in PerchFieldType classes contain this check.
         *
         * #TODO: Find out why this check is needed.
         */

        if ($post === false) {
            $post = $_POST;
        }

        /*
         * Retrieve the table id from the template. This is used to
         * look up cell values in the $post array.
         */

        $id = $this->Tag->id();

        /*
         * Store the column titles defined in the template.
         */

        $titles = explode(',', $this->Tag->cols());

        foreach ($titles as $title) {
            $store[self::TITLES_KEY][] = trim($title);
        }

        /*
         * Read the table dimensions from the template.
         */

        $cols = PerchUtil::count($titles);
        $rows = intval(trim($this->Tag->rows()));

        /*
         * Build the table data array. Trailing blank rows
         * are ignored.
         */

        $data = array();
        $last_populated_row = -1;

        for ($row = 0; $row < $rows; $row++) {

            $rowdata = array();

            for ($col = 0; $col < $cols; $col++) {

                $val = '';
                $cid = $this->get_cellid($id, $row, $col);

                if (isset($post[$cid]) && trim($post[$cid]) !== '') {
                    $val = trim($post[$cid]);
                    $last_populated_row = $row;
                }

                $rowdata[] = $val;
            }

            $data[] = $rowdata;
        }

        /*
         * Trim trailing blank rows.
         */

        if ($last_populated_row + 1 < $rows) {
            array_splice($data, $last_populated_row + 1);
        }

        /*
         * Store table data.
         */

        $store[self::DATA_KEY] = $data;

        return $store;
    }

    /*
     * Function: get_processed()
     *
     * Purpose: Outputs the HTML for the table using the data stored
     * by get_raw().
     *
     * Returns: An HTML string.
     *
     * $raw is the raw data for the table, either from get_raw()
     * or the database.
     */

    public function get_processed($raw=false)
    {
        /*
         * Get the column headings and table data.
         */

        $data = $raw[self::DATA_KEY];
        $titles = $raw[self::TITLES_KEY];

        /*
         * Get the table dimensions. Remember that these can be
         * limited in the template.
         */

        $rows = PerchUtil::count($data);
        $limit = intval($this->Tag->limitrows());
        $rows = ($limit > 0 && $limit < $rows) ? $limit : $rows;

        $cols = PerchUtil::count($titles);
        $limit = intval($this->Tag->limitcols());
        $cols = ($limit > 0 && $limit < $cols) ? $limit : $cols;

        /*
         * If the "noheaders" attribute is set the <table> and <thead> tags
         * are omitted from the output.
         *
         * Note: $this->Tag returns boolean(false) if the attribute is missing
         * or set to "false". Otherwise it returns the attribute value as a string.
         */

        $noheaders = ($this->Tag->noheaders() === false) ? false : PerchUtil::bool_val($this->Tag->noheaders());

        /*
         * Build the HTML string
         */

        $s = '';

        if (!$noheaders) {

            $s .= '<table><thead><tr>';
            for ($col = 0; $col < $cols; $col++) {
                $s .= '<th>'.$titles[$col].'</th>';
            }
            $s .= '</tr></thead>';
        }

        $s .= '<tbody>';

        for ($row = 0; $row < $rows; $row++) {

            $s .= '<tr>';
            for ($col = 0; $col < $cols; $col++) {
                $s .= '<td>'.$data[$row][$col].'</td>';
            }
            $s .= '</tr>';
        }

        $s .= '</tbody>';

        if (!$noheaders) {

            $s .= '</table>';
        }

        return $s;
    }

    public function get_search_text($raw=false)
    {
        return '';
    }

    public function add_page_resources()
    {
        if ($this::$page_resources_added) {
            return;
        }

        $tab = "\t";
        $asset_path = PERCH_LOGINPATH.'/addons/fieldtypes/tabular/assets';

        $Perch = Perch::fetch();
        $Perch->add_head_content(PHP_EOL);
        $Perch->add_head_content($tab.'<!-- BEGIN: Include files for the tabular fieldtype -->');
        $Perch->add_head_content(PHP_EOL);
        $Perch->add_head_content($tab.'<link rel="stylesheet" href="'.$asset_path.'/tabular.css" />');
        $Perch->add_head_content(PHP_EOL);
        $Perch->add_head_content($tab.'<!-- END: Include files for the tabular fieldtype -->');
        $Perch->add_head_content(PHP_EOL);

        $this::$page_resources_added = true;
    }
}

?>
