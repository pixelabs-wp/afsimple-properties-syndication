<?php

class AFPM_Main
{   
    //first function that runs when the plugin loads up
    function run(){
       global $upload_csv;
        add_action( 'admin_menu', array( $upload_csv, 'upload_csv_add_plugin_page' ) );
        add_action( 'admin_init', array( $upload_csv, 'upload_csv_page_init' ) );
        
    }

    
function uploadCSVProcess() {
  $AFPM_Main = new AFPM_Main;
    $filename = $_FILES['file']['name'];
  
  /* Choose where to save the uploaded file */
  $location = plugin_dir_path( __FILE__ )."upload/".$filename;
  $return = plugin_dir_url( __FILE__ )."upload/".$filename;
  
  $moved = move_uploaded_file($_FILES["file"]["tmp_name"], $location );
  $datetime = date("d-M-Y H:i:s a");
  if( $moved ) {
   
  
    
  
  
    $CSVtoJSON = $AFPM_Main->CSVtoJSON($location,$filename);
    echo '{ "datetime":"'.$datetime.'", "uploadStatus":"success", "filename":"'.$filename.'", "json":'.$CSVtoJSON.' }';
  } else {
  echo '{ "datetime":"'.$datetime.'", "uploadStatus":"failure", "filename":null, "failureMessage":"'.$_FILES["file"]["error"].'" }';
  }
  
  die();
     
  }
    
    //Converts CSV to a bisc json file
    function CSVtoJSON($target, $filename){
        $file = fopen($target, 'r');
        $CSVArray = array();
        while (($line = fgetcsv($file)) !== FALSE) {
            if($line[0] != "Address"){
                array_push($CSVArray,$line);
            }
        }
        fclose($file);




        if(empty($CSVArray)){
            return '{"uploadStatus":"success", "filename":null, "dataStatus":"empty"}';
        } else {
            $filename = str_replace(".csv", "", $filename);
            $res = file_put_contents(PATHAFPM.'JSON/'.$filename.'.json', json_encode($CSVArray));

            if($res){
                $file = URLAFPM.'JSON/'.$filename.'.json';
                
                return '{"uploadStatus":"success", "filename":"'.addslashes($file).'", "dataStatus":"populated"}';
            } else {
                return '{"uploadStatus":"failed", "filename":null, "dataStatus":null}';
            }
        }
    
        
    }    

    //Upload Products

    public static function upload(){

        $JSON = $_POST["data"];
        $productData = $JSON;
        $item = AFPM_Main::renderPayload($productData);

        
    
        $user_id = get_current_user(); 
        $post_id = wp_insert_post( array(
            'post_author' => $user_id,
            'post_title' => $item['address'],
            'post_content' => $item['description'],
            'post_status' => 'publish',
            'post_type' => "product",
        ) );
        
        $result = array($item, "postId"=>$post_id);
       
      
        wp_set_object_terms( $post_id, 'simple', 'product_type' );
        update_post_meta( $post_id, '_visibility', 'visible' );
        update_post_meta( $post_id, '_stock_status', 'instock');
        update_post_meta( $post_id, 'total_sales', '0' );
        update_post_meta( $post_id, '_downloadable', 'no' );
        update_post_meta( $post_id, '_virtual', 'no' );
        update_post_meta( $post_id, '_regular_price', '' );
        update_post_meta( $post_id, '_sale_price', '' );
        update_post_meta( $post_id, '_purchase_note', '' );
        update_post_meta( $post_id, '_featured', 'no' );
        update_post_meta( $post_id, '_weight', '' );
        update_post_meta( $post_id, '_length', '' );
        update_post_meta( $post_id, '_width', '' );
        update_post_meta( $post_id, '_height', '' );
        update_post_meta( $post_id, '_sku', $item['parcel'] );
        update_post_meta( $post_id, '_product_attributes', array() );
        update_post_meta( $post_id, '_sale_price_dates_from', '' );
        update_post_meta( $post_id, '_sale_price_dates_to', '' );
        update_post_meta( $post_id, '_price', '' );
        update_post_meta( $post_id, '_sold_individually', '' );
        update_post_meta( $post_id, '_manage_stock', 'yes' );
        update_post_meta( $post_id, '_backorders', 'no' );
        update_post_meta( $post_id, '_stock', '1' );
        update_post_meta( $post_id, '_instock', 'yes' );
        update_post_meta( $post_id, '_regular_price', $item['price']  );
        update_post_meta( $post_id, '_sale_price', $item['salePrice'] );
        update_post_meta( $post_id, '_price', $item['price']  );

        foreach ($item["images"] as $value) {
          AFPM_Main::uploadImage($value, $post_id);
        }


        wp_send_json(json_encode($result));
    }
    
    public static function uploadImage($image_url, $insert_id){
     
      $image = pathinfo($image_url);//Extracting information into array.
      $image_name = $image['basename'];
      $upload_dir = wp_upload_dir();
      $image_data = file_get_contents($image_url);
      $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name);
      $filename = basename($unique_file_name);
      
          if ($image != '') {
              // Check folder permission and define file location
              if (wp_mkdir_p($upload_dir['path'])) {
                  $file = $upload_dir['path'] . '/' . $filename;
              } else {
                  $file = $upload_dir['basedir'] . '/' . $filename;
              }
              // Create the image  file on the server
              file_put_contents($file, $image_data);
              // Check image file type
              $wp_filetype = wp_check_filetype($filename, null);
              // Set attachment data
              $attachment = array(
                  'post_mime_type' => $wp_filetype['type'],
                  'post_title' => sanitize_file_name($filename),
                  'post_content' => '',
                  'post_status' => 'inherit',
              );
              // Create the attachment
              $attach_id = wp_insert_attachment($attachment, $file, $insert_id);
              // Include image.php
              require_once ABSPATH . 'wp-admin/includes/image.php';
              // Define attachment metadata
              $attach_data = wp_generate_attachment_metadata($attach_id, $file);
              // Assign metadata to attachment
              wp_update_attachment_metadata($attach_id, $attach_data);
              // And finally assign featured image to post
              $thumbnail = set_post_thumbnail($insert_id, $attach_id);
          }
      
    }
    public static function renderPayload($line){

        
        $date_created = date("d M Y");
        $date_created_gmt = gmdate("d M Y", strtotime($date_created));
    
        $date_created = (new DateTime($date_created))->format('c');
        $date_created_gmt = (new DateTime($date_created_gmt))->format('c');
        $address = $line[0];
        $slug = str_replace(" ", "-", $address);
        $slug = AFPM_Main::RemoveSpecialChar($slug);
        $slug = strtolower($slug);
        $description = $line[1];
        $description = preg_replace("/\r\n|\r|\n/", '<br/>', $description);
        $propertyType = $line[2];
        $parcel = $line[3];
        $lotSizeAcres = $line[4];
        $lotSizeSqft = $lotSizeAcres * 43560;
        $zoning = $line[5];
        $utilities = $line[6];
        $deedType = $line[7];
        $legalText = $line[8];
        $roadAccess = $line[9];
        $gps = $line[10];
        $annualTaxes = $line[11];
        $city = $line[12];
        $state = $line[13];
        $postalCode = $line[14];
        $county = $line[15];
        $country = $line[16];
        $hoa = $line[17];
        $descriptionCustom = $line[18];
        $descriptionCustom = preg_replace("/\r\n|\r|\n/", '<br/>', $descriptionCustom);
        $productImage = $line[19];
        $price = $line[24];
        $salePrice = $line[25];  
        $fullStreetAddress = $line[21];
        $latLong = explode(',', $gps);
        $lat = trim($latLong[0]);
        $long = trim($latLong[1]);
        $enableDeposit = $line[26];
        $depositAmount = $line[27];
        if($line[20] == "" || $line[20] == NULL){
          $imagesP = '';
        } else {
          $imagesP = AFPM_Main::getImages($line[20],$parcel);
        }
        if($line[22] == "" || $line[22] == NULL){
          $categoriesP = '';
        } else {
          $categoriesP = AFPM_Main::getCategories($line[22]);
        }
        if($line[23] == "" || $line[23] == NULL){
          $tagsP = '';
        } else {
          $tagsP = AFPM_Main::getTags($line[23]);
        }
       
        
        $payload = array("date_created" => $date_created,
        "date_created_gmt"=>$date_created_gmt,
        "address"=>$address,
        "slug"=>$slug,
        "description"=>$description,
        "propertyType"=>$propertyType,
        "parcel"=>$parcel,
        "lotSizeAcres"=>$lotSizeAcres,
        "lotSizeSqft"=>$lotSizeSqft,
        "zoning"=>$zoning,
        "utilities"=>$utilities,
        "deedType"=>$deedType,
        "legalText"=>$legalText,
        "roadAccess"=>$roadAccess,
        "gps"=>$gps,
        "latitude"=>$lat,
        "longitude"=>$long,
        "annualTaxes"=>$annualTaxes,
        "streetAddress"=>$fullStreetAddress,
        "city"=>$city,
        "state"=>$state,
        "postalCode"=>$postalCode,
        "county"=>$county,
        "country"=>$country,
        "hoa"=>$hoa,
        "descriptionCustom"=>$descriptionCustom,
        "productImage"=>$productImage,
        "price"=>$price,
        "salePrice"=>$salePrice,
        "images"=>$imagesP,
        "categories"=>$categoriesP,
        "tags"=>$tagsP,
        "enableDeposit"=>$enableDeposit,
        "depositAmount"=>$depositAmount
        );
    
        return $payload;
    
    }
    //Parse Images
   public static function getImages($images,$parcel){

      $imagesArr = explode('|', $images);
      $imagesArr = array_map('trim', $imagesArr);
    

  
      return $imagesArr;
    }
      //Remove Special Characters
    public static function RemoveSpecialChar($str) {
    
        // Using str_replace() function
        // to replace the word
        $res = str_replace( array( '\'', '"',
        ',' , ';', '<', '>' ), '', $str);
    
        // Returning the result
        return $res;
    }
      //Get All Tags in JSON Format
    public static function getTags($tags){
    

        $tagArr = explode('|', $tags);


        //payloadTags holds all the tags ids
        $payloadTags = array();
        foreach($tagArr as $tag){
            $tag = get_term_by('name', $tag, 'product_tag');
            $id = $tag->id;
        
          array_push($payloadTags, $id);
        }
    
       

        return $payloadTags;
    }

      //Get All Categories in JSON Format
    public static function getCategories($categories){
    
        $catArr = explode('|', $categories);
        //payloadCats holds all the category ids
        $payloadCats = array();
        foreach($catArr as $cat){
          $category = get_term_by( 'name', $cat, 'product_cat' );
          $id = $category->term_id;
          array_push($payloadCats, $id);
        }
        return $payloadCats;
    }


   
}
