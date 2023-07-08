<?php
  
  $f3->route('POST /cts/@reference', "Clickthroughs->update");
  $f3->route('POST /cts', "Clickthroughs->create");
  $f3->route('GET /cts', "Clickthroughs->index");
  $f3->route('GET /cts/@reference', "Clickthroughs->click");

  class Clickthroughs{
    
    public static function build($reference){
      global $_SETTINGS;
      return $_SETTINGS["baseurl"]."/cts/$reference";
    }

    /**
     * @api {get} /cts/:reference Update
     * @apiName Update
     * @apiGroup Clickthroughs
     * @apiDescription Update the 'next' url for an existing clickthrough
     * @apiUse Auth
     * @apiVersion 1.0.0
     * 
     * @apiParam {Integer} :reference Clickthrough unique reference
     * @apiParam {String} next Next URL to load, any instance of the string "@reference" will be replaced with the :reference value
     *
     * @apiSuccess {String} data "Updated"
     */
    public static function update($f3){
      Sessions::validate();
      
      $data = array(":reference" => $f3->get("PARAMS.reference"), ":next"=> $f3->get("REQUEST.next"));

      validate_input($data, array(
        ':reference' => 'required|freetext',
        ':next' => 'href'
      ));

      $sql = "update clickthroughs set next = :next where reference = :reference";
      dbquery($sql, $data);
      json_ok("Updated");
    }


    /**
     * @api {post} /cts Create
     * @apiName Create
     * @apiGroup Clickthroughs
     * @apiDescription Create a new clickthrough and return the reference number
     * @apiUse Auth
     * @apiVersion 1.0.0
     *
     * @apiParam {String} type Type of clickthrough, eg 'verify-email'
     * @apiParam {String} next Next URL to load, any instance of the string "@reference" will be replaced with the :reference value
     *
     * @apiSuccess {String} reference The new clickthrough reference, store this for use in an email etc
     * @apiSuccess {String} url The new clickthrough URL, store this for use in an email etc
     * @apiSuccessExample {json} Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *    "reference": "25ee",
     *    "href": "/api/cts/25ee"
     *  }
     * @apiErrorExample {json} Error-Response: 
     *  HTTP/1.1 400 Bad Request
     *  "Invalid clickthrough type"
     * @apiExample {curl} Example usage:
     * curl \
     *    -X POST \
     *    -H "api-key: {api-key}" \
     *    -H "Content-Type: application/json" \
     *    -d '{
     *      "type": "verify-email",
     *      "next": "/stuff/@reference"
     *    }' "https://connect.ausnetservices.com.au/api/cts"
     */

    public static function create($f3){
      Sessions::validate();
      $data = array(
        ":type" => $f3->get("REQUEST.type"),
        ":next" => $f3->get("REQUEST.next")
      );

      validate_input($data, array(
        ':type' => 'required|alpha_dash',
        ':next' => 'href'
      ));
      
      // check this is an acceptable clickthrough type
      if(!in_array($data[":type"], array("verify-email"))){
        json_error("Invalid clickthrough type", 400);
      }

      // generate a new clickthrough reference
      $sql = "
        insert into clickthroughs
        (type, next)
        values
        (:type, :next)
      ";
      $result = dbquery($sql, $data);

      $clickthroughid = dblastinsertid();
      $reference = base_convert($clickthroughid, 10, 36);
      $sql = "update clickthroughs set reference = :reference where clickthroughid = :clickthroughid";
      dbquery($sql, array(":reference" => $reference, ":clickthroughid" => $clickthroughid));
      json_ok(array("reference" => $reference, "href" => self::build($reference)), 201);
    }

    /**
     * @api {get} /cts Index
     * @apiName Index
     * @apiGroup Clickthroughs
     * @apiDescription Retrieve the status of existing clickthroughs
     * @apiUse Auth
     * @apiVersion 1.0.0
     *
     * @apiParam {String} after Clicked after this UTC timestamp (YYYY-MM-DD HH24:MI:SS)
     * @apiParam {String} before Clicked before this UTC timestamp (YYYY-MM-DD HH24:MI:SS)
     * @apiParam {String} reference Only return clickthrough with this unique reference
     * @apiParam {Integer} clicked Only return if clickthrough has been clicked
     * @apiParam {String} type Type of clickthrough
     *
     * @apiSuccess {Object[]} clickthroughs List of clickthroughs
     * @apiSuccess {String} .type Clickthrough type (eg download-sms, validate-email)
     * @apiSuccess {String} .reference Unique reference for clickthrough
     * @apiSuccess {Integer} .clicked_state Has the clickthrough been clicked
     * @apiSuccess {String} .clicked_utc Timestamp last clicked (UTC, YYYY-MM-DD HH24:MI:SS)
     * @apiSuccess {String} .url URL of clickthrough
     * @apiSuccess {String} .next The new address the user will be redirected to
     * @apiSuccessExample {json} Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *    "clickthroughs": [
     *      {
     *        "type": "verify-email",
     *        "reference": "25dy",
     *        "clicked_state": 1,
     *        "clicked_utc": "2016-03-08 06:01:50",
     *        "url": "/api/cts/25dy"
     *      },
     *      ...
     *    ]
     *  }
     * @apiExample {curl} Example usage:
     * curl -X GET \
     *  -H "api-key: {api-key}" \
     *  -H "Content-Type: application/json" \
     *  "https://connect.ausnetservices.com.au/api/cts?after=2016-03-07"
     */
    public static function index($f3){
      
       
      Sessions::validate();
      $data = $f3->get("REQUEST");

      validate_input($data, array(
        'after' => 'date',
        'before' => 'date',
        'reference' => 'alpha_numeric',
        'clicked' => 'integer',
        'type' => 'alpha_dash'
      ));

      $after = $f3->get("REQUEST.after");
      $before = $f3->get("REQUEST.before");
      $reference = $f3->get("REQUEST.reference");
      $clicked = $f3->get("REQUEST.clicked");
      $type = $f3->get("REQUEST.type");

      $wheres = array();
      if($after){
        $wheres[] = "clicked_utc > :after";
        $data[":after"] = $after;
      }
      if($before){
        $wheres[] = "clicked_utc < :before";
        $data[":before"] = $before;
      }
      if($clicked !== null){
        $wheres[] = "clicked_state = :clicked";
        $data[":clicked"] = $clicked;
      }

      if($reference){
        $wheres[] = "reference = :reference";
        $data[":reference"] = $reference;
      }

      if($type){
        $wheres[] = "type = :type";
        $data[":type"] = $type;
      }

      $sql = "select type, reference, clicked_state, clicked_utc, next from clickthroughs ";
      if($wheres){
        $sql .= " where ".implode(" and ", $wheres);
      }
      $sql .= " order by clickthroughid desc LIMIT 1001";
      $results = dbquery($sql, $data);
      $rows = $results->fetchAll(PDO::FETCH_ASSOC);
      if(count($rows) == 1001) json_error("Too many results found, please limit your search", 500);
      $ret = array();
      foreach($rows as $row){
        $row["url"] = self::build($row["reference"]);
        $row["clicked_state"] = (int) $row["clicked_state"];
        $ret[] = $row;
      }
      json_ok(array("clickthroughs" => $ret));

    }

    /**
     * @api {get} /cts/:reference Click
     * @apiName Click
     * @apiGroup Clickthroughs
     * @apiVersion 1.0.0
     *
     * @apiDescription 'Action' a clickthrough, this happens when a user clicks a tiny URL link
     * The action is recorded, ie the last_clicked date is updated and state changes to clicked=1, then 
     * a redirect is performed if the clickthrough has a 'next' property set. For example, the user can be
     * redirected to a generic thank you page or a page with their specific reference.  
     *
     * @apiParam {String} :reference Unique reference of the clickthrough
     *
     */
    public static function click($f3){
      // check the reference exists
      global $_SETTINGS;
      $reference = $f3->get("PARAMS.reference");

      validate_input(array("reference" => $reference), array(
        'reference' => 'alpha_numeric'
      ));


      $sql = "select * from clickthroughs where reference = :reference";
      $result = dbquery($sql, array(":reference" => $reference));
      $ct = $result->fetch(PDO::FETCH_ASSOC);

      // if we got nothing, return 404
      if(!$ct){
        header("Location: ".$_SETTINGS["ui"]."/404");
        exit;
      }
        //json_error(null, 404);

      // we found it, update the status in the DB
      $sql = "update clickthroughs set clicked_state = 1, clicked_utc = utc_timestamp() where clickthroughid = :clickthroughid";
      dbquery($sql, array(":clickthroughid" => $ct["clickthroughid"]));

      // redirect to the next url
      $ct["next"] = str_replace("@reference", $ct["reference"], $ct["next"]);
      header("Location: ".$_SETTINGS["ui"].$ct["next"]);

      // if there's a 'next' url set, go there
     // json_ok(array("href" => $ct["next"]));
      
    }

  }
  
  
?>