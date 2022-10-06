<?php

define('ACCESS_TOKEN', 'PAC_TOKEN_HERE');
define('BASE_URL', 'https://api.infusionsoft.com/crm/rest');

class InfuseApi {

    public static function make_api_call($end_point, $method = "GET", $query_data = array(), $params_data = array()) {

        $url = BASE_URL . $end_point . '/?' . http_build_query($query_data);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                'X-Keap-API-Key:' . ACCESS_TOKEN,
                'Accept: application/json, */*',
                'Content-Type: application/json; charset=utf-8'
            ),
        ));

        if( !empty( $params_data ) ){
            $json_string = json_encode($params_data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json_string);
        }

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response, true);
    }

    public static function make_xmlrpc_call($params_data){
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.infusionsoft.com/crm/xmlrpc/v1',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $params_data,
        CURLOPT_HTTPHEADER => array(
                'Content-Type: application/xml',
                'X-Keap-API-Key:' . ACCESS_TOKEN
            ),
        ));

        $xmlstring = curl_exec($curl);

        // {"fault":{"faultstring":"Spike arrest violation. Allowed rate : MessageRate{messagesPerPeriod=5, periodInMicroseconds=5000000, maxBurstMessageCount=1.0}","detail":{"errorcode":"policies.ratelimit.SpikeArrestViolation"}}}

        if( is_json($xmlstring) ){ 
            sleep(5); 
            return self::make_xmlrpc_call($params_data);
        }

        $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode( $json, true);

        $response = array_key_search_recursive($array, 'member');

        curl_close($curl);

        return $response;
    }

    public static function get_contact_model( $query_data = array() ){
        $end_point = "/v1/contacts/model";
        $contacts = self::make_api_call($end_point, "GET", $query_data);

        return $contacts;
    }

    public static function get_contacts( $query_data = array() ){
        $end_point = "/v1/contacts";
        $contacts = self::make_api_call($end_point, "GET", $query_data);

        return $contacts;
    }

    public static function get_contact_by_id($id, $query_data){
        $end_point = "/v1/contacts/{$id}";
        $contacts = self::make_api_call($end_point, "GET", $query_data);

        return $contacts;
    }

    public static function update_contact( $contact_id, $params_data = array() ){
        $end_point = "/v1/contacts{$contact_id}";

        $contact = self::make_api_call($end_point, "PATCH", array(), $params_data);

        return $contact;
    }

    public static function add_update_contact( $params_data = array() ){
        $end_point = "/v1/contacts";

        $params_data['duplicate_option'] = ( isset( $params_data['duplicate_option'] ) ) ? $params_data['duplicate_option'] : "Email";
        $params_data['opt_in_reason'] = ( isset( $params_data['opt_in_reason'] ) ) ? $params_data['opt_in_reason'] : "Contact opted-in through webform";

        $contact = self::make_api_call($end_point, "PUT", array(), $params_data);

        return $contact;
    }

    public static function get_users(){
        $end_point = "/v1/users";
        $users = self::make_api_call($end_point, "GET");

        return $users['users'];
    }

    public static function apply_contact_tags( $contact_id, array $tag_ids){
        $end_point = "/v1/contacts/{$contact_id}/tags";

        $params_data['tagIds'] = $tag_ids;

        $contact = self::make_api_call($end_point, "POST", array(), $params_data);

        return $contact;
    }

    public static function get_order_by_id($id){
        $end_point = "/v1/orders/{$id}";
        $query_data = array(
            'optional_properties' => [
                'custom_fields'
            ]
        );
        $order = self::make_api_call($end_point, "GET", $query_data );

        return $order;
    }

    public static function get_order_model(){
        $end_point = "/v1/orders/model";

        $response = self::make_api_call($end_point, "GET" );

        return $response;
    }


    public static function achieve_api_goal ($contact_id, $call_name, $integration = 'qj959') {
        $end_point = "/v1/campaigns/goals/{$integration}/{$call_name}";

        $params_data['contact_id'] = $contact_id;

        $response = self::make_api_call($end_point, "POST", array(), $params_data);

        return $response;
    } 


    public static function get_tags_list ($query_data = array()) {
        $end_point = "/v1/tags";

        $response = self::make_api_call($end_point, "GET", $query_data);

        return $response;
    }

    public static function create_tag ( $name, $category_id = 0, $description = '' ) {
        $end_point = "/v1/tags";

        $params_data = array(
            'name' => $name,
            'description' => $description,
            'category' => array(
                'id' => $category_id
            )
            );

        $response = self::make_api_call($end_point, "POST", array(), $params_data);

        return $response;
    }

    public static function get_hook_subscriptions($event_key = null, $hook_url = null, $key = null, $status = null){
        $end_point = '/v1/hooks';
        
        $params_data = array(
            'eventKey' => $event_key,
            'hookUrl' => $hook_url,
            'key' => $key,
            'status' => $status,
        );

        $response = self::make_api_call($end_point, "GET");
        return $response;
    }

    public static function add_hook_subscriptions($event_key, $hook_url){
        $end_point = '/v1/hooks';
        
        $params_data = array(
            'eventKey' => $event_key,
            'hookUrl' => $hook_url,
        );

        $response = self::make_api_call($end_point, "POST", array(), $params_data);
        return $response;
    }

    public static function remove_hook_subscription($key){
        $end_point = "/v1/hooks/{$key}";        

        $response = self::make_api_call($end_point, "DELETE" );
        return $response;
    }

    public static function verify_hook_subscription($key){
        
        $end_point = "/v1/hooks/{$key}/verify";       

        $response = self::make_api_call($end_point, "POST" );
        return $response;
    }

    public static function list_hook_types(){
        $end_point = '/v1/hooks/event_keys';
        
        $response = self::make_api_call($end_point, "GET");
        return $response;
    }

    public static function xmlrpc_data_query($tableName, $limit, $page, $queryField, $query, $returnFields, $orderBy, $sortOrder = 0){

        $return_fields_xml = '';
        foreach($returnFields as $reurnfield){
            $return_fields_xml .= "<value><string>{$reurnfield}</string></value>";
        }

        $params_data = "<?xml version='1.0' encoding='UTF-8'?>
        <methodCall>
          <methodName>DataService.query</methodName>
          <params>
            <param>
              <value><string>privateKey</string></value>
            </param>
            <param>
              <value><string>{$tableName}</string></value>
            </param>
            <param>
              <value><int>{$limit}</int></value>
            </param>
            <param>
              <value><int>{$page}</int></value>
            </param>
            <param>
              <value><struct>
                <member>
                    <name>{$queryField}</name>
                    <value><string>{$query}</string></value>
                </member>
              </struct></value>
            </param>
            <param>
              <value><array>
                <data>{$return_fields_xml}</data>
              </array></value>
            </param>
            <param>
              <value><string>{$orderBy}</string></value>
            </param>
            <param>
              <value><boolean>{$sortOrder}</boolean></value>
            </param>
          </params>
        </methodCall>";

        $response = self::make_xmlrpc_call($params_data);

        return $response;
    }

}


function get_order_array(){
    $codestring = file_get_contents('contents.json');

    $codeArray = json_decode($codestring, true);

    return $codeArray;
}

function array_key_search_recursive (array $haystack, $needle ){
    $found = [];
    foreach ($haystack as $key => $value) {
        # code...
        if( $key === $needle ){
            $found[] = $value;
        } else if(is_array($value)){
            $tmp_array = array_key_search_recursive ($value, $needle );
            $found = array_merge($found, $tmp_array);
        } 
    }
    return $found;
}

function is_json($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
 }



function get_custom_field_key_values($customFieldsItems){

    $codeArray = array();

    foreach($customFieldsItems as $customFieldsItem){

        $varsArray = array();

        if( !empty( $customFieldsItem['options'] ) ){
            
            $cf_options = $customFieldsItem['options']; 

            
            foreach($cf_options as $options){
                
                if(!empty($options['options'])){
                    $innerOptions = $options['options'];
                    foreach($innerOptions as $innerOption){
                        $varsArray[ $innerOption['id'] ] = $innerOption['label'];
                    }

                } else {
                    $varsArray[ $options['id'] ] = $options['label'];
                }

            }

        } else {
            $varsArray[ $customFieldsItem['id'] ] = $customFieldsItem['label'];
        }

        $codeArray['_'.$customFieldsItem['field_name']] = $varsArray;
    }

    return $codeArray;
}