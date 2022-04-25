<?php
/*
   Plugin Name: Downsell woocommerce
   Plugin URI: https://lucarigutti.it/downsell-woocommerce
   description: To show the opposite of upsell
   Version: 0.1
   Author: Luca Rigutti
   Author URI: https://lucarigutti.it
   License: GPL2
   */

  //woocommerce_product_options_related
  if ( !class_exists( 'woocommerceDownsell' ) )
  {
      class woocommerceDownsell
      {
        public function getProductIds($postId)
        {
          global $wpdb;
          $product_id_downsell = intval( $postId );
          $query = "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_upsell_ids' and meta_value like '%i:".$product_id_downsell."%'";
          $product_ids = $wpdb->get_results( $query, OBJECT );
          return $product_ids;
        }
        public function showDownsell()
        {
          ?>
          <div class="options_group">
            <p class="form-field">
              <label for="downsell_ids"><?php esc_html_e( 'Downsell', 'woocommerce' ); ?></label>
              <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="downsell_ids" name="downsell_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval( $post->ID ); ?>">
                <?php

                      $product_ids = $this->getProductIds(wc_get_product()->get_id()); //$post->ID
                /*
                  This template is from this file: websiteFolder/wp-content/plugins/woocommerce/includes/admin/meta-boxes/views/html-product-data-linked-products.php
                  https://stackoverflow.com/questions/56404003/in-woocommerce-how-to-find-all-products-for-which-a-certain-product-is-a-cross
                */

                foreach ( $product_ids as $product ) {
                  $product_id = $product->post_id;
                  $product = wc_get_product( $product_id );
                  if ( is_object( $product ) ) {
                    echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
                  }
                }
                ?>
              </select> <?php echo wc_help_tip( __( 'Downsell are products which you recommend instead of the currently viewed product, for example, products that are less expensive.', 'woocommerce' ) ); // WPCS: XSS ok. ?>
            </p>

          <?php
        }

        public function saveDownsell($postId)
        {

          if($postId!="")
          {

            //Other doc: https://stackoverflow.com/questions/45199599/how-to-add-more-custom-field-in-linked-product-of-woocommerce
            $downsellIds = $_POST['downsell_ids'];
            //TODO: Check if the id are valid
            /*
              TODO: Get all ids from getProductIds, then if the id is not set on $downsellIds, need to remove
                    In the same time, remove id from $downsellIds if match. What remains on the Array is the new id to add,
                    so foreach that,load the product and adding the new upsell $postId
                    */

            $upsellList = $this->getProductIds($postId);
            //throw new Exception('$downsellIds:'.json_encode($downsellIds)." with: ". json_encode($upsellList));

            foreach($upsellList as $upsell )
              if(!in_array($upsell->post_id,$downsellIds))
              {
                $productToChange = wc_get_product($upsell->post_id);
                $upsellIdsOfProduct = $productToChange-> get_upsell_ids();
                $productToChange->set_upsell_ids(array_diff($upsellIdsOfProduct,[$postId]));
                $productToChange->save();
              }
              else
                $downsellIds = array_diff($downsellIds,[$upsell->post_id]);
            foreach($downsellIds as $downsellId)
              {
                $productToChange = wc_get_product($downsellId);
                $upsellIdsOfProduct = $productToChange-> get_upsell_ids();
                array_push($upsellIdsOfProduct,intval($postId));
                $productToChange->set_upsell_ids($upsellIdsOfProduct);
                $productToChange->save();
              }
            }

        }

        public function __construct()
        {
            add_action("woocommerce_product_options_related",array($this, "showDownsell"));
            add_action( 'woocommerce_process_product_meta', array($this,'saveDownsell') );
        }
      }
    }

  $woocommerceDownsell = new woocommerceDownsell();

 ?>
