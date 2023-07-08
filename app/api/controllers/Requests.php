<?php

  $f3->route('GET /requests', "Requests->index");
  $f3->route('DELETE /requests/@requestid', "Requests->delete");
  $f3->route('GET /requests/@requestid', "Requests->get");
  $f3->route('POST /requests', "Requests->create");

  class Requests extends Common{

    /**
     * @api {get} /requests Index
     * @apiName Index
     * @apiGroup Requests
     * @apiDescription List existing requests for metering data
     * @apiUse Auth
     * @apiVersion 1.0.0
     *
     * @apiParam {String} state Request state (eg 'new')
     * @apiParam {Date} after Requests received after this date YYYY-MM-DD
     * @apiParam {Date} before Requests received before this date YYYY-MM-DD
     *
     * @apiSuccess {Object[]} array
     * @apiSuccess {Number} .requestid Unique id of the request
     * @apiSuccess {String} .state State of this request (new, deleted)
     * @apiSuccess {String} .created_utc Timestamp when the request was created (YYYY-MM-DD HH24:MI:SS)
     * @apiExample {curl} Example usage:
     * curl -X GET \
     *   -H "api-key: {api-key}" \
     *   "https://connect.ausnetservices.com.au/api/requests?after=2015-01-01&state=deleted"
     * @apiSuccessExample {json} Success-Response:
     *   {
     *     "requests": [
     *       {
     *         "requestid": "141",
     *         "state": "deleted",
     *         "created_utc": "2016-06-22 06:11:42"
     *       },
     *     ]
     *   }
     *
     */
    public static function index($f3){
      // must be authenticated 
      Sessions::validate();

      $sql = "select requestid, state, created_utc from requests";

      $wheres = array();
      $data = array();

      // filter by state
      $state = $f3->get("REQUEST.state");
      if($state){
        $wheres[] = "state = :state";
        $data[":state"] = trim(strtolower($state));
      }

      // filter by date, must be created after this date
      $after = $f3->get("REQUEST.after");
      if($after){
        $wheres[] = "created_utc >= :after";
        $data[":after"] = trim(strtolower($after));
      }

      // filter by date, must be created before this date
      $before = $f3->get("REQUEST.before");
      if($before){
        $wheres[] = "created_utc < :before";
        $data[":before"] = trim(strtolower($before));
      }

      validate_input($data, array(
        ':state' => 'alpha',
        ':after' => 'date',
        ':before' => 'date'
      ));

      if($wheres){
        $sql = "$sql where ".implode(" and ", $wheres);
      }

      $sql .= " order by created_utc desc";

      $result = dbquery($sql, $data);
      json_ok(array("requests" => $result->fetchAll(PDO::FETCH_ASSOC)));
    }

    /**
     * @api {delete} /requests/:request_id Delete
     * @apiName Delete
     * @apiGroup Requests
     * @apiDescription Delete an existing request (ideally after we've downloaded it)
     * @apiUse Auth
     * @apiVersion 1.0.0
     *
     * @apiParam {Integer} :request_id Unique identifier of the request 
     * @apiExample {curl} Example usage:
     * curl -X DELETE -H "api-key: {api-key}" "https://connect.ausnetservices.com.au/api/requests/11"
     * @apiSuccess {String} data "Request deleted"
     * @apiSuccessExample {json} Success-Response:
     *  HTTP/1.1 200 OK
     *  "Request deleted"
     */
    public static function delete($f3){
      
      // must be authenticated 
      Sessions::validate();

      // delete this request if it exists 
      $requestid = $f3->get("PARAMS.requestid");
      $sql = "update requests set state = 'deleted', data='deleted',ekey='deleted' where requestid = :requestid limit 1";
      dbquery($sql, array(":requestid" => $requestid));
      json_ok("Request deleted", 200);
    }

    // 
     /**
     * @api {get} /requests/:requestid Get
     * @apiName Get
     * @apiGroup Requests
     * @apiDescription Get an existing request
     * @apiUse Auth
     * @apiVersion 1.0.0
     *
     * @apiParam {Integer} :requestid Unique identifier of the request 
     *
     * @apiSuccess {Integer} requestid Unique identifier of the request
     * @apiSuccess {String} created_utc UTC timestamp when request was created
     * @apiSuccess {String} state State of request (new, deleted)
     * @apiSuccess {String} ekey Payload encrypted with public key. Decrypt this with your private key to recover the payload. Payload structure is exactly the same as that posted in the 'create' call.
     * @apiSuccess {String} epayload Encrypted payload
     * @apiSuccess {String[]} consentforms Array of consent form files
     * @apiExample {curl} Example usage:
     *  curl -X GET -H "api-key: {api-key}" "https://connect.ausnetservices.com.au/api/requests/10"
     * @apiSuccessExample {json} Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *    "request": {
     *      "requestid": 10,
     *      "created_utc": "2016-06-22 06:10:52",
     *      "state": "new",
     *      "ekey": "sLNT+QMO+llKfQWXL1wouJz6pSMuZtd8+dZxEm50ITl/0sz4cgSm4ol3+u0g0QTYcIM1MQYo3vJ7wnrFBgVUDREajC6YbYMKt9dRojUfnD9ucuZGDU1tAOHMSu6v+F7QhFqDy7RhzFifd8laRrXYUCKXVP1NKYzuonEQvc2UK39AekSR5AjKn0snsmKM0kKT74K+cn5eryIxuxQ/gnK/4gcLOoQsxRqHLTuFjGECVFG8OLrgPmdYmiilwLLkV+/SOBfVUqODy5a+k1VfYTtxNHvi8BX/JFdBqHVnTeKjiG36NIW8d05hENtIOkdTc7Ow+Icrs5A4qpdDgw1eyNCggA==",
     *      "epayload": "05aY/qj4SyQejdCs//ANXeispQJC7Hekmd9PV0aE4zE3ZNyHZMd6n81q1S9aYdYZeCyZMqYxlrGjL/4quPhO77HieTPlkzgsmJ0Oem1qdZvtiFW8E95EsV76EYm3zviKveRGLaHfVxx5ukaTl1AmicPFD4e98hwLaKTLSZrLjMKTbduC83HtFlJA0ONhxNBnltL4ZQOE9e4j4KkCWXgI8tSWGl4jS3HHHu6QmpDzX0KDzzUL/4eWLWiH+Ungx9NixkzDGgj+7jb4OPWt4mTTSQedxaLxM92fXNmgLCaA1DfMOPucEOhIRjIeDwjjt6NJ2xr3gXgx5PFq8xBZi6NFlnMh4l2JUPRY4YNs81ASilio2Sl5ygFuFxH6ioRzw8+igh3Vw89McBLDgpKJxnPauwdgTDgTw4Vc+wg33GI+vdWdvMEaxRw+BbqNeR6Db5BK+LMptlYTzmCj7DYeNrSccc5U2AifbhCbXOM0T4WpXt1opjp/e0pSa8FbcTvo7Q1GuyB8eHA1vjLfR4z9NjLgQcEvbMZ4rgDt7LUcnQD6+qN0Q4QpsAy+roJ6Wp1hzti5mrU+H+kAqF4CIDNs2V6/uVlkD/CRVrnCHNo63a4QLgS7Z+j/IYGNFc3kqVqbzCmXFsssJNSurC+zvIogJycGBw=="
     *    }
     *  }
     */
    public static function get($f3){

      // must be authenticated 
      Sessions::validate();

      $requestid = $f3->get("PARAMS.requestid");
      $sql = "select * from requests where requestid = :requestid";
      $result = dbquery($sql, array(":requestid" => $f3->get("PARAMS.requestid")));

      $r = $result->fetch(PDO::FETCH_ASSOC);

      if(!$r){
        json_error(null, 404);
      }

      //$tmp = json_decode($r["data"], true);
      $req = array(
        "requestid" => (int)$r["requestid"],
        "created_utc" => $r["created_utc"],
        "state" => $r["state"],
        "ekey" => $r["ekey"],
        "epayload" => $r["data"]
      );

      json_ok(array("request" => $req));
    }

    /**
     * @api {post} /requests Create
     * @apiName Create
     * @apiGroup Requests
     * @apiDescription Create a new requeust for metering data
     * @apiVersion 1.0.0
     *
     * @apiParam {Object} request
     * @apiParam {String} .email Email address of requestor
     * @apiParam {String} .first_name First name of requestor
     * @apiParam {String} .last_name Last name of requestor
     * @apiParam {String} .business_name Business name (if applicable)
     * @apiParam {String} .phone_primary Primary phone number
     * @apiParam {String} .phone_secondary Alternative phone number
     * @apiParam {String} .address_flat Flat Number
     * @apiParam {String} .address_number Street number
     * @apiParam {String} .address_street_name Street Name
     * @apiParam {String} .address_street_type Street type
     * @apiParam {String} .address_city City
     * @apiParam {String} .address_state State
     * @apiParam {String} .address_postcode Postcode
     * @apiParam {String} .nmi National Meter Identifier
     * @apiParam {String} .meter_number Meter number
     * @apiParam {String} .han_mac HAN Device MAC address
     * @apiParam {String} .han_install_code HAN Device install code
     * @apiParam {Integer} .eula Accepted EULA flag
     *
     * @apiParam {String} version The UI version from which the request originated
     * 
     * @apiExample {curl} Example usage:
     * curl \
     *   -X POST \
     *   -H "Content-Type: application/json" \
     *   -d '{
     *    "request" : {
     *      "email"                 : "kenneth.chew@ausnetservices.com.au",
     *      "first_name"            : "Kenneth",
     *      "last_name"             : "Chew",
     *      "business_name"         : "",
     *      "phone_primary"         : "0396956975",
     *      "phone_secondary"       : "",
     *      "address_flat"          : "",
     *      "address_number"        : "",
     *      "address_street_name"   : "Southbank",
     *      "address_street_type"   : "Boulevard",
     *      "address_city"          : "Southbank",
     *      "address_state"         : "VIC",
     *      "address_postcode"      : "3006",
     *      "nmi"                   : "6305123456",
     *      "meter_number"          : "4123456",
     *      "han_vendor"            : "Greenwave",
     *      "han_model"             : "IHD",
     *      "han_mac"               : "1a2b3c4d5e6f",
     *      "han_install_code"      : "1a2b3c4d5e6f7g8h",
     *      "eula"                  : 1
     *    },
     *    "version" : "0.0.1"
     *  }' "https://connect.ausnetservices.com.au/api/requests"
     * @apiSuccess {String} msg "Request created"
     * @apiSuccessExample {json} Success-Response:
     *  HTTP/1.1 201 OK
     *  "Request created"
     */
    public static function create($f3){

      //$version = $f3->get("REQUEST.version");
      $version = self::get_version();
      // details of request
      $request = $f3->get("REQUEST.request");
      // check details of request
      if(strpos($request["email"],"@") == -1) throw new Exception("Invalid email address");
      if(!isset($request["eula"])) throw new Exception("EULA must be accepted");

      validate_input($request, array(
        'email' => 'required|valid_email|max_len,200',
        'first_name' => 'required|freetext|max_len,150',
        'last_name' => 'required|freetext|max_len,150',
        'business_name' => 'freetext|max_len,300',
        'phone_primary' => 'required|freetext|max_len,50',
        'phone_secondary' => 'freetext|max_len,50',
        'address_flat' => 'freetext|max_len,20',
        'address_number' => 'freetext|max_len,10',
        'address_street_name' => 'required|freetext|max_len,100',
        'address_street_type' => 'freetext|max_len,20',
        'address_city' => 'required|freetext|max_len,50',
        'address_state' => 'required|freetext|max_len,20',
        'address_postcode' => 'required|integer|exact_len,4',
        'nmi' => 'required|alpha_numeric|min_len,10|max_len,11',
        'meter_number' => 'required|numeric|exact_len,7',
        'han_vendor' => 'required|freetext|max_len,128',
        'han_model' => 'required|freetext|max_len,128',
        'han_mac' => 'required|alpha_numeric|min_len,12|max_len,16',
        'han_install_code' => 'required|alpha_numeric|min_len,16|max_len,36',
        'eula' => 'required|integer'
      ));
      
      $sql = "
        insert into requests
        (data, created_utc, state, ekey)
        values
        (:data, utc_timestamp(), 'new', :ekey)
      ";

      $data = json_encode(array("request" => $request, "version" => $version));
      $enc = self::encrypt($data);

      dbquery($sql, array(":data" => base64_encode($enc["data"]), ":ekey" => base64_encode($enc["ekey"])));

      json_ok("Request created", 201);
    }

    /**
     * get_version
     * Gets current version number
     */
    public static function get_version(){
      $version = null;
      if(file_exists("../version.txt")){
        $version = trim(file_get_contents("../version.txt"));
      };
      return $version;
    } 
  }

?>