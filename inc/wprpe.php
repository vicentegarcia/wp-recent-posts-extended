<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://vicentegarcia.com
 * @since      1.0.0
 *
 * @package    WP_Recent_Posts_Extended
 * @subpackage WP_Recent_Posts_Extended/inc
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Recent_Posts_Extended
 * @subpackage WP_Recent_Posts_Extended/inc
 * @author     Vicente Garcia <contacto@vicentegarcia.com>
 */


class wprpe extends WP_Widget {

    public function __construct()
    {
        $wprpe_optionss = array('classname' => 'wprpe', 'description' => "Un sencillo widget para mostrar los últimos artículos por categoría." );
        parent::__construct('wprpe','WP Recent Posts Extended', $wprpe_optionss);
        wp_enqueue_style( 'wprpe-styles', plugins_url( 'wprpewidget.css', __FILE__ ));
    }

    function widget($args,$instance)
    {
        extract($args);
        ?>
        <div id="widget_wprpe">
            <?php
                $wprpe_content = '';
                $exclude = $instance["wprpe_categories_exclude"];
                $cat_excludes = (empty($exclude))? '' : 'exclude='.$exclude;
                $all_categories = get_categories($cat_excludes);
                foreach ($all_categories as $cat)
                {
                    $wprpe_content .= $this->get_wprpe_content($cat->cat_ID, $cat->cat_name, '<h2>', '</h2>');
                }
                echo $wprpe_content;
            ?>
        </div>
        <?php
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance["wprpe_categories_exclude"] = ( ! empty( $new_instance['wprpe_categories_exclude'] ) ) ? strip_tags($new_instance["wprpe_categories_exclude"]) : '';
        return $instance;
    }

    function form($instance)
    {
        $categories_exclude = empty ($instance['wprpe_categories_exclude']) ? '' : $instance['wprpe_categories_exclude'];
        ?>
         <p>
            <label for="<?php echo esc_attr ($this->get_field_id('wprpe_categories_exclude')); ?>">
            <?php esc_attr_e( 'Categorías a excluir (separadas por comas): ', 'text_domain' ); ?>
            </label>
            <input
            class="widefat"
            id="<?php echo esc_attr ($this->get_field_id('wprpe_categories_exclude')); ?>"
            name="<?php echo esc_attr ($this->get_field_name('wprpe_categories_exclude')); ?>"
            type="text" value="<?php echo $categories_exclude; ?>">
        </p>
         <?php
    }


    public function get_wprpe_content($categoryID,$catname,$before,$after)
    {
        global $wpdb;

        $title = $catname;
        $numPosts = 5;
        $category = $categoryID;

        $posts_by_category  =   $this->category_get_posts($numPosts,$category);

        $stringwprpe  =  ($before.$title.$after."\n");
        $stringwprpe .=  "<ul>\n";
        for ($x=0;$x<count($posts_by_category);$x++ )
        {
            $stringwprpe .= '<li><a href="'.$posts_by_category[$x]['permalink'].'">'.$posts_by_category[$x]['title']. '</a></li>';
        }
        $stringwprpe .= "</ul>\n";

        return $stringwprpe;

    }

    public function category_get_posts($numPosts = '0',$category = '')
    {
        global $wpdb, $wp_db_version;

        $notfirst = false;

        if($category == ''):
            $sql = "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'post'";
        else:
            if($wp_db_version >= 6124):
                $sql = "SELECT $wpdb->posts.ID ";
                $sql.= "FROM $wpdb->posts, $wpdb->term_relationships, $wpdb->term_taxonomy ";
                $sql.= "WHERE $wpdb->posts.post_status = 'publish' ";
                $sql.= "AND $wpdb->posts.post_type = 'post' ";
                $sql.= 'AND ';
                $sql.= '( ';
                $sql.= "$wpdb->posts.ID = $wpdb->term_relationships.object_id ";
                $sql.= "AND $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id ";
                $sql.= "AND $wpdb->term_taxonomy.term_id = $category ";
                $sql.= ')';
            else:
                $sql = "SELECT $wpdb->posts.ID ";
                $sql.= "FROM $wpdb->posts, $wpdb->post2cat ";
                $sql.= "WHERE $wpdb->posts.post_status = 'publish' ";
                $sql.= "AND $wpdb->posts.post_type = 'post'";
                $sql.= "AND $wpdb->post2cat.post_id = $wpdb->posts.ID ";
                $sql.= "AND $wpdb->post2cat.category_id = $category";
            endif;
        endif;

        if ($numPosts > 0) { $sql.= " limit ".$numPosts; }
        $the_ids = $wpdb->get_results($sql);

        $idsPosts = (array) array_keys($the_ids);

        $sql = "SELECT $wpdb->posts.post_title, $wpdb->posts.ID ";
        $sql .= " FROM $wpdb->posts";
        $sql .= " WHERE";

        foreach ($idsPosts as $id)
        {
            if($notfirst) $sql .= " OR";
            else $sql .= " (";
            $sql .= " $wpdb->posts.ID = ".$the_ids[$id]->ID;
            $notfirst = true;
        }
        $sql .= ')';
        if ($numPosts > 0) {
        $sql.= " limit ".$numPosts;
        }

        $posts_by_category = $wpdb->get_results($sql);

        if ($posts_by_category)
        {
            foreach ($posts_by_category as $item)
            {
                // $posts_results[] = array('title'=>str_replace('"','',stripslashes($item->post_title)),'permalink'=>post_permalink($item->ID));
                $posts_results[] = array('title'=>str_replace('"','',stripslashes($item->post_title)), 'permalink'=>get_permalink($item->ID));
            }
            return $posts_results;
        }
        else
        {
            return false;
        }
    }

}