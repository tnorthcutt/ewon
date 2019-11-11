<?php
//
// Sample M2Web Library source code. Delivered as M2Web API sample code. Not supported by eWON s.a.
//
// Disclaimer:
//    This code is EXPERIMENTAL and has not followed the usual validation process of eWON s.a. R&D.
//    Delivered as is. eWON s.a. takes no responsibility for anything bad that can result from the use of this code.
//    Actual mileage may vary. Price does not include tax, title, and license. Some assembly required. Each sold
//    separately. Batteries not included. Objects in mirror are closer than they appear. If conditions persist,
//    contact a physician. Keep out of reach of children. Avoid prolonged exposure to direct sunlight. Keep in a cool
//    dark place.
//    You've been warned!
//

/** Builds an URL to access the M2Web API of the portal */
function buildUrl($action, $credentials, $params) {
    $query = http_build_query(array_merge($credentials, $params));
    return "https://m2web.talk2m.com/t2mapi/" . $action . "?" . $query;
}

/** Builds an URL to access an eWON using the M2Web API*/
function buildEwonUrl($ewonPath, $ewon, $params, $credentials) {
    $allParams = array_merge($params,
        array("t2mdeviceusername" => $ewon["t2mdeviceusername"],
            "t2mdevicepassword" => $ewon["t2mdevicepassword"]));
    return buildUrl ( "get/" . $ewon ["name"] . "/" . $ewonPath, $credentials, $allParams );
}

/** Call an API on the portal */
function callApi($url) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt ($ch, CURLOPT_URL, $url);

    // Get the response and close the channel.
    $response = curl_exec ( $ch );

    if ($response === false) {
        echo "Failed to ".$action." : " . curl_error ( $ch );
    }

    curl_close($ch);

    return $response;
}

/** calls an M2Web JSON API */
function callJsonApi($url) {
    $response = callApi($url);
    $jsonResponse = false;
    if ($response === false) {
        echo "Failed to ".$action." : " . curl_error ( $ch );
    } else {
        $jsonResponse = json_decode ( $response );
        if (! isset ( $jsonResponse->success )) {
            echo "Failed to $url : $response";
        } else if (! $jsonResponse->success) {
            echo "Failed to $url : $jsonResponse->code - $jsonResponse->message";
        }
    }
    return $jsonResponse;
}

function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

/** parses an eWON 'csv' file */
function parseCsv($csv) {
    $lines  = explode("\n", $csv);
    $result = array();
    foreach($lines as $line) {
        if (!empty($line)) {
            $result[] = parseCsvLine($line);
        }
    }
    return $result;
}

/** parses an eWON 'csv' line */
function parseCsvLine($line) {
    $parts = explode(";", $line);
    $result = array();

    while (!empty($parts)) {
        $elt = array_shift($parts);

        // handle fields starting end ending with "
        // merge the fields that were exploded because they contain ";"
        // and remove the starting end ending "
        if (startsWith($elt, "\"")) {
            $part = substr($elt,1);
            while (!endsWith($part, "\"") && !empty($parts)) {
                $elt = array_shift($parts);
                $part = $part.";".$elt;
            }
            if (endsWith($part, "\"")) {
                $part = substr($part, 0, strlen($part) -1);
            }
            $result[] = $part;
        } else {
            $result[] = $elt;
        }
    }
    return $result;
}

/** takes a CSV file (where the first line contains the column names)
 * and returns an array of associative arrays
 * E.g.:
 * 		"name";"id"
 * 		"EwonName";1
 * 		"FlexyName";2
 * => [
 * 		[ "name" => "EwonName" , "id" => 1 ],
 * 		[ "name" => "FlexyName" , "id" => 2 ] ]
 *
 */
function transformCsv($csv, $itemName) {
    $parsed = parseCsv($csv);
    if (empty($parsed)) {
        return array();
    }

    $columnNames = array_shift($parsed);

    $result = array();

    foreach($parsed as $line) {
        $columnId = 0;
        $tag = array();
        foreach($line as $column) {
            $tag[$columnNames[$columnId]] = $column;
            $columnId++;
        }
        $result[$tag[$itemName]] = $tag;
    }

    return $result;
}

/** Returns the list of eWONs of the account in the following form :
 * [ {"id":173681,"name":"Flexy_01","encodedName":"Flexy_01","status":"online","description":"","customAttributes":["","",""],"m2webServer":"m2web.talk2m.com","lanDevices":[],"ewonServices":[]},
 *   {"id":173711,"name":"Flexy_02","encodedName":"Flexy_02","status":"online","description":"","customAttributes":["","",""],"m2webServer":"m2web.talk2m.com","lanDevices":[],"ewonServices":[]},
 *   ...
 * ]
 */
function getEwons($credentials) {
    $result = callJsonApi(buildUrl("getewons", $credentials, array()));
    if ($result) {
        return $result->ewons;
    } else {
        return $result;
    }
}

/** Returns information about one eWON in the following form :
 * {"id":173711,"name":"Flexy_02","encodedName":"Flexy_02","status":"online","description":"","customAttributes":["","",""],"m2webServer":"m2web.talk2m.com","lanDevices":[],"ewonServices":[]}
 */
function getEwon($credentials, $ewonName) {
    $result = callJsonApi(buildUrl("getewon", $credentials, array("name" => $ewonName)));
    if ($result) {
        return $result->ewon;
    } else {
        return $result;
    }
}


/** returns an array containing the tags in the following form :
 * {"Bt_Auto":
 * 		{"TagId":"1","TagName":"Bt_Auto","Value":"0","AlStatus":"0","AlType":"0","Quality\"\r":"65472\r"},
 *  "Bt_Manu":
 *  	{"TagId":"2","TagName":"Bt_Manu","Value":"0","AlStatus":"0","AlType":"0","Quality\"\r":"65472\r"},
 *  "Bt_Start":
 *  	{"TagId":"3","TagName":"Bt_Start","Value":"0","AlStatus":"0","AlType":"0","Quality\"\r":"65472\r"},
 *  ...
 *  }
 */
function getTags($credentials, $ewon) {
    $url = buildEwonUrl("rcgi.bin/ParamForm", $ewon, $credentials, array( "AST_Param" => "\$\$dtIV\$ftT"));
    $result = callApi($url);
    $tags = transformCsv($result, "TagName");

    return $tags;
}


function setTagValue($credentials, $ewon, $tagName, $tagValue) {
    $url = buildEwonUrl("rcgi.bin/UpdateTagForm", $ewon, $credentials, array( "TagName" => $tagName, "TagValue" => $tagValue));
    $result = callApi($url);
}
?>
