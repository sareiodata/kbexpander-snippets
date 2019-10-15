<?php
namespace kbx;
use Symfony\Component\HttpFoundation\Request;
/**
 * Admin Pages Handler
 */
class Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
    }

    /**
     * Register our menu page
     *
     * @return void
     */
    public function admin_menu() {
        global $submenu;

        $capability = 'manage_options';
        $slug       = 'kbx-charts';

        $hook = add_submenu_page(
            'edit.php?post_type=kb',
            __( 'Report Charts', 'kbexpander' ),
            __( 'Report Charts', 'kbexpander' ),
            $capability,
            $slug,
            [ $this, 'plugin_page_charts' ]
        );

        add_action( 'load-' . $hook, [ $this, 'init_hooks'] );

        $capability = 'manage_options';
        $slug       = 'kbx-tables';

        $hook = add_submenu_page(
            'edit.php?post_type=kb',
            __( 'Report Tables', 'kbexpander' ),
            __( 'Report Tables', 'kbexpander' ),
            $capability,
            $slug,
            [ $this, 'plugin_page_tables' ]
        );

        add_action( 'load-' . $hook, [ $this, 'init_hooks'] );
    }

    /**
     * Initialize our hooks for the admin page
     *
     * @return void
     */
    public function init_hooks() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Load scripts and styles for the app
     *
     * @return void
     */
    public function enqueue_scripts() {
        //wp_enqueue_style( 'baseplugin-admin' );
        //wp_enqueue_script( 'baseplugin-admin' );
    }

    /**
     * Render our admin page
     *
     * @return void
     */
    public function plugin_page_charts() {
        $request = Request::createFromGlobals();
        $user = $request->get('user', '');
        $kb_id = $request->get('kb', '');
        $start = $request->get('start', '');
        $end = $request->get('end', '');

        $chart_data = $this->getChartData($user, $kb_id, $start, $end);
        $labels = json_encode($chart_data[0]);
        $data = json_encode($chart_data[1]);

?>
<div class="wrap">
    <h1>Report Charts</h1>
    <form id="kb-form-filter" action="">
    <table class="kb-filter">
        <tr>
            <td>
                <select name="user">
                    <option value="">Choose a user...</option>
                    <?php echo $this->getUsers($user); ?>
                </select>
            </td>
            <td>
                <select name="kb">
                    <option value="">Choose a KB...</option>
                    <?php echo $this->getKbs($kb_id); ?>
                </select>
            </td>
            <td>
                <label>Start: <input type="date" name="start" value="<?php echo $start; ?>"></label>
            </td>
            <td>
                <label>End: <input type="date" name="end" value="<?php echo $end; ?>"></label>
            </td>
            <td>
                <button type="submit"  class="button-primary">Filter</button>
            </td>
        </tr>
    </table>
    </form>

    <canvas id="myChart" width="4" height="1"></canvas>
</div>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/css/select2.min.css" integrity="sha256-FdatTf20PQr/rWg+cAKfl6j4/IY3oohFAJ7gVC3M34E=" crossorigin="anonymous" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js" integrity="sha256-d/edyIFneUo3SvmaFnf96hRcVBcyaOy96iMkPez1kaU=" crossorigin="anonymous"></script>
<script>
    jQuery(document).ready(function() {
        jQuery('.kb-filter select').select2();

        jQuery('#kb-form-filter').submit(function(event){
            event.preventDefault();
            //let values = {};
            let url = document.location.href;
            url = url.slice( 0, url.indexOf('&page=kbx-charts') );
            url += '&page=kbx-charts';
            jQuery.each(jQuery(this).serializeArray(), function(i, field) {
                //values[field.name] = field.value;
                if ( field.value != '' ){
                    url += "&"+field.name+"="+field.value;
                }
            });
            document.location = url;
        })
    });


</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.min.js" integrity="sha256-xKeoJ50pzbUGkpQxDYHD7o7hxe0LaOGeguUidbq6vis=" crossorigin="anonymous"></script>
<script>
    var ctx = document.getElementById('myChart').getContext('2d');
    var chart = new Chart(ctx, {
        // The type of chart we want to create
        type: 'line',

        // The data for our dataset
        data: {
            labels: <?php echo $labels ?>,
            datasets: [{
                label: 'My First dataset',
                backgroundColor: 'rgb(255, 99, 132)',
                borderColor: 'rgb(155, 99, 132)',
                data: <?php echo $data ?>
            }]
        },

        // Configuration options go here
        options: {
            legend: {
                display: false
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem) {
                        return tooltipItem.yLabel;
                    }
                }
            }
        }
    });
</script>
        <style>
            .kb-filter input,
            .kb-filter select{
                padding: 5px 10px;
                width: 250px;
                border-radius: 5px;
                border-color: rgb(170, 170, 170)
            }
        </style>
        <?php
    }

    /**
     * Render our admin page
     *
     * @return void
     */
    public function plugin_page_tables() {
        $request = Request::createFromGlobals();
        $user = $request->get('user', '');
        $kb = $request->get('kb', '');
        $start = $request->get('start', '');
        $end = $request->get('end', '');

        ?>
        <div class="wrap">
            <h1>Report Charts</h1>
            <form id="kb-form-filter" action="">
                <table class="kb-filter">
                    <tr>
                        <td>
                            <select name="user">
                                <option value="">Choose a user...</option>
                                <?php echo $this->getUsers($user); ?>
                            </select>
                        </td>
                        <td>
                            <select name="kb">
                                <option value="">Choose a KB...</option>
                                <?php echo $this->getKbs($kb); ?>
                            </select>
                        </td>
                        <td>
                            <label>Start: <input type="date" name="start" value="<?php echo $start; ?>"></label>
                        </td>
                        <td>
                            <label>End: <input type="date" name="end" value="<?php echo $end; ?>"></label>
                        </td>
                        <td>
                            <button type="submit"  class="button-primary">Filter</button>
                        </td>
                    </tr>
                </table>
            </form>

            <canvas id="myChart" width="4" height="1"></canvas>
        </div>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/css/select2.min.css" integrity="sha256-FdatTf20PQr/rWg+cAKfl6j4/IY3oohFAJ7gVC3M34E=" crossorigin="anonymous" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js" integrity="sha256-d/edyIFneUo3SvmaFnf96hRcVBcyaOy96iMkPez1kaU=" crossorigin="anonymous"></script>
        <script>
            jQuery(document).ready(function() {
                jQuery('.kb-filter select').select2();

                jQuery('#kb-form-filter').submit(function(event){
                    event.preventDefault();
                    //let values = {};
                    let url = document.location.href;
                    url = url.slice( 0, url.indexOf('&page=kbx-tables') );
                    url += '&page=kbx-tables';
                    jQuery.each(jQuery(this).serializeArray(), function(i, field) {
                        //values[field.name] = field.value;
                        if ( field.value != '' ){
                            url += "&"+field.name+"="+field.value;
                        }
                    });
                    document.location = url;
                })
            });


        </script>
        <style>
            .kb-filter input,
            .kb-filter select{
                padding: 5px 10px;
                width: 250px;
                border-radius: 5px;
                border-color: rgb(170, 170, 170)
            }
        </style>
        <?php
    }


    private function getUsers($selected)
    {
        //,
        $args = array(
            'role__in'    => array('administrator', 'editor'),
            'order'   => 'ASC'
        );
        $users = get_users( $args );
        $content = '';
        foreach ( $users as $user ) {
            $username = esc_attr($user->user_login);
            if($selected == $user->user_login){
                $content .= "<option selected value='$username'>$username</option>";
            } else {
                $content .= "<option value='$username'>$username</option>";
            }
        }
        return $content;
    }

    private function getKbs($selected)
    {
        //,
        $args = array(
            'post_type'    => 'kb',
            'posts_per_page'   => -1
        );
        $kbs = get_posts( $args );
        $content = '';
        foreach ( $kbs as $kb ) {
            $kb_id = esc_attr($kb->ID);
            $kb_title = esc_html($kb->post_title);

            $categories = get_the_terms($kb_id, 'kbcategory');
            $kb_terms = '';
            if($categories !== false){
                foreach( $categories as $category) {
                    $kb_terms .= "#" . $category->name . ' ';
                }
            }
            if($selected == $kb_id) {
                $content .= "<option selected value='$kb_id'>$kb_terms $kb_title</option>";
            } else {
                $content .= "<option value='$kb_id'>$kb_terms $kb_title</option>";
            }
        }
        return $content;
    }

    private function getChartData($user = '', $kb = '', $start = '', $end = '')
    {
        global $wpdb;

        $between_date = $wpdb->prepare(" (`timestamp` BETWEEN %s AND %s ) ", array($start." 00:00:00", $end . " 23:59:59"));
        if (empty($start) || empty($end) ){
            $start = date('Y-m-d', strtotime('-30 days'));
            $end = date('Y-m-d', time());
            $between_date = $wpdb->prepare(" (`timestamp` BETWEEN %s AND %s )", array($start." 00:00:00", $end . " 23:59:59"));
        }

        $where_user = '';
        if(!empty($user)){
            $where_user = $wpdb->prepare(" (`user_name` LIKE %s) ", $user);
        }

        $where_kb = '';
        if(!empty($kb)){
            $where_kb = $wpdb->prepare(" (`kb_id` LIKE %s) ", $kb);
        }

        $table_name = $wpdb->prefix . 'kbx_logs';
        $query = "SELECT * from $table_name WHERE" . $between_date;

        if (!empty($where_user)){
            $query .= " AND " . $where_user;
        }

        if (!empty($where_kb)){
            $query .= " AND " . $where_kb;
        }

        $result = $wpdb->get_results( $query, OBJECT_K  );

        if ( $wpdb->last_error !== '' )
            return false;

        $index = $start;
        $labels = [];
        $labels_data = [];
        while( $index <= $end){
            $labels[] = date('M-d', strtotime($index));
            $labels_data[date('Y-m-d', strtotime($index))] = 0;
            $index = date('Y-m-d', strtotime("$index +1 days"));
        }

        foreach($result as $key => $log){
            if( array_key_exists(date('Y-m-d', strtotime($log->timestamp)), $labels_data) ) {
                $labels_data[date('Y-m-d', strtotime($log->timestamp))]++;
            }
        }

        return [$labels, array_values($labels_data)];
    }
}
