<?php

require_once('xmlrpc/lib/xmlrpc.inc');

$DEBUG = false;

// DBPedia lookup interface
$BASE_DBPEDIA_LOOKUP_URI = "http://lookup.dbpedia.org/api/search.asmx/KeywordSearch?QueryClass=string&MaxHits=5&QueryString=";

// Talis store interface
$BASE_TALIS_LOOKUP_URI ="http://api.talis.com/stores/kwijibo-dev3/services/sparql?output=json&query=";
$BASE_TALIS_BROWSE_URI = "http://kwijibo.talis.com/voiD/dataset?";

// RKB store interface
$BASE_RKB_LOOKUP_URI = "http://void.rkbexplorer.com/sparql/?format=json&query=";
$BASE_RKB_BROWSE_URI =  "http://void.rkbexplorer.com/browse/?";



$NAMESPACES = array(
	'xsd' => 'http://www.w3.org/2001/XMLSchema#',
  	'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
  	'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
	'owl' => 'http://www.w3.org/2002/07/owl#',
  	'foaf' => 'http://xmlns.com/foaf/0.1/',
  	'dc' => 'http://purl.org/dc/elements/1.1/', 
  	'dcterms' => 'http://purl.org/dc/terms/',
  	'skos' => 'http://www.w3.org/2004/02/skos/core#',
  	'sioc' => 'http://rdfs.org/sioc/ns#',
  	'sioct' => 'http://rdfs.org/sioc/types#',
  	'xfn' => 'http://gmpg.org/xfn/11#',
  	'twitter' => 'http://twitter.com/',
  	'dbpres' => 'http://dbpedia.org/resource/',
  	'dbpprop' => 'http://dbpedia.org/property/',
  	'void' => 'http://vocab.dowhatimean.net/neologism/void-tmp#'   	
);

$SELF_DS = ":myDS";

$BASE_TTL = "@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix foaf: <http://xmlns.com/foaf/0.1/> .
@prefix dcterms: <http://purl.org/dc/terms/> .
@prefix void: <http://rdfs.org/ns/void#> .
@prefix : <#> .

## your dataset\n";


/* ve2 INTERFACE */


//// POST interface

if(isset($_POST['dsParams'])){ // generate voiD in Turtle
	$dsParams = json_decode($_POST['dsParams'], true);
	echo createVoiDTTL($dsParams);
}

if(isset($_POST['inspect'])){ // inspect voiD in Turtle
	echo inspectVoiD($_POST['inspect']); // saves voiD in tmp/ and calls then Web inspector via GET
	//echo inspectVoiDLive($_POST['inspect']); // direct POST of voiD content to Web inspector
}

if(isset($_POST['announce'])){ // announce a voiD URI
	$result = "<p>Result of announce process:</p>";
	$result .= pingback("Sindice", "http://sindice.com/xmlrpc/api", "voiD file", $_POST['announce']);
	$result .= ping("RKB voiD store", "http://void.rkbexplorer.com/submit/?action=uri", "uri", $_POST['announce']);
	$result .= ping("Talis voiD store", "http://kwijibo.talis.com/voiD/submit", "url", $_POST['announce']);
	$result .= ping("PingtheSemanticWeb.com", "http://pingthesemanticweb.com/rest/", "url", $_POST['announce']);
	echo $result;
}

//// GET interface

if(isset($_GET['validate'])){ 	
	echo validateHTTPURI($_GET['validate']);
}

if(isset($_GET['lookupSubject'])){ 	
	echo lookupSubjectInDBPedia($_GET['lookupSubject']);
}

if(isset($_GET['lookupVoiDViaHompage'])){
	// use Talis store as default
	$lookupURI = $BASE_TALIS_LOOKUP_URI;
	$browseURI = $BASE_TALIS_BROWSE_URI;
	
	if(isset($_GET['store'])){ // store specified, choose store
		if($_GET['store'] == "RKB"){
			$lookupURI = $BASE_RKB_LOOKUP_URI;
			$browseURI = $BASE_RKB_BROWSE_URI;
		}
		else {
			$lookupURI = $BASE_TALIS_LOOKUP_URI;
			$browseURI = $BASE_TALIS_BROWSE_URI;
		}
	}

	echo lookupVoiD($lookupURI, $browseURI, $_GET['lookupVoiDViaHompage']);
}

if(isset($_GET['listVoiD'])){
	// use Talis store as default
	$lookupURI = $BASE_TALIS_LOOKUP_URI;
	
	if(isset($_GET['store'])){ // store specified, choose store
		if($_GET['store'] == "RKB"){
			$lookupURI = $BASE_RKB_LOOKUP_URI;
		}
		else {
			$lookupURI = $BASE_TALIS_LOOKUP_URI;
		}
	}
	echo listVoiD($lookupURI);
}

if(isset($_GET['lookupPrefix'])){
	$prefix = $_GET['lookupPrefix'];
	
	if(substr($prefix, 0, 4) == "http") {
		echo $prefix;
	}
	else {
		echo lookupPrefix($prefix);
	}
}

/* ve2 METHODS */
function createVoiDTTL($dsParams){
	global $DEBUG;
	global $NAMESPACES;
	global $SELF_DS;
	global $BASE_TTL;
	$retVal = $BASE_TTL;
	$dsURI = $dsParams["dsURI"];
	$dsHomeURI = $dsParams["dsHomeURI"];
	$dsName = $dsParams["dsName"];
	$dsDescription = $dsParams["dsDescription"];
	$dsExampleURIList = $dsParams["dsExampleURIList"];
	$dsTopicURIList = $dsParams["dsTopicURIList"];
	$tdsList = $dsParams["tdsList"];
	$dsPublisherURI = $dsParams["dsPublisherURI"];
	$dsSourceURI = $dsParams["dsSourceURI"];
	$dsLicenseURI = $dsParams["dsLicenseURI"];
	$dsVocURIList = $dsParams["dsVocURIList"];
	$dsSPARQLEndpointURI = $dsParams["dsSPARQLEndpointURI"];
	$dsLookupURI = $dsParams["dsLookupURI"];
	$dsDumpURI = $dsParams["dsDumpURI"];
	
	
	// the dataset
	if($dsURI){
		$retVal .= "<$dsURI> rdf:type void:Dataset ;\n";
	}
	else {
		$retVal .= "$SELF_DS rdf:type void:Dataset ;\n";
	}
	$retVal .= " foaf:homepage <$dsHomeURI> ;\n";
	$retVal .= " dcterms:title \"$dsName\" ;\n";
	$retVal .= " dcterms:description \"$dsDescription\" ;\n";
	if($dsPublisherURI){
		$retVal .= " dcterms:publisher <$dsPublisherURI> ;\n";
	}
	if($dsSourceURI){
		$retVal .= " dcterms:source <$dsSourceURI> ;\n";
	}
	if($dsLicenseURI){
		$retVal .= " dcterms:license <$dsLicenseURI> ;\n";
	}
	if($dsSPARQLEndpointURI){
		$retVal .= " void:sparqlEndpoint <$dsSPARQLEndpointURI> ;\n";
	}
	if($dsLookupURI){
		$retVal .= " void:uriLookupEndpoint <$dsLookupURI> ;\n";
	}
	if($dsDumpURI){
		$retVal .= " void:dataDump <$dsDumpURI> ;\n";
	}
	if($dsVocURIList){
		$i = 1;
		foreach ($dsVocURIList as $dsVocURI) {
			$retVal .= " void:vocabulary <$dsVocURI> ;\n";
			$i++;
		}
	}	
	if($dsExampleURIList){
		$i = 1;
		foreach ($dsExampleURIList as $dsExampleURI) {
			$retVal .= " void:exampleResource <$dsExampleURI>";
			if(count($dsTopicURIList) == 0 && count($tdsList) == 0) {
				if($i < count($dsExampleURIList)) $retVal .= " ;\n";
				else $retVal .= " .\n";
			}
			else $retVal .= " ;\n";
			$i++;
		}
	}
	if($dsTopicURIList){
		$i = 1;
		foreach ($dsTopicURIList as $dsTopicURI) {
			$retVal .= " dcterms:subject <$dsTopicURI>";
			if(count($tdsList) == 0) {
				if($i < count($dsTopicURIList)) $retVal .= " ;\n";
				else $retVal .= " .\n";
			}
			else $retVal .= " ;\n";
			$i++;
		}
	}
	if($tdsList){
		$i = 1;
		foreach ($tdsList as $tdsListItem) {
			$retVal .= " void:subset " . $SELF_DS ."-DS$i";
			if($i < count($tdsList)) $retVal .= " ;\n";
			else $retVal .= " .\n";
			$i++;
		}
	}

	// linksets 
	if($tdsList){
		$i = 1;
		$retVal .= "\n## datasets you link to\n";
		foreach ($tdsList as $tdsListItem) {
			$tdsListURI = $tdsListItem["tdsHomeURI"];
			$tdsLinkType = $tdsListItem["tdsLinkType"];
			$tdsName = $tdsListItem["tdsName"];
			$tdsDescription = $tdsListItem["tdsDescription"];
			$tdsExampleURI = $tdsListItem["tdsExampleURI"];
			
			$retVal .= "\n# interlinking to :DS$i\n";
			$retVal .= ":DS$i rdf:type void:Dataset ;\n";
			$retVal .= " foaf:homepage <$tdsListURI> ;\n";
			$retVal .= " dcterms:title \"$tdsName\" ;\n";
			$retVal .= " dcterms:description \"$tdsDescription\"";
			if($tdsListItem["tdsExampleURI"]) {
				$retVal .= " ; \n";
				$retVal .= " void:exampleResource <$tdsExampleURI> .\n\n";
			}
			else 	$retVal .= " . \n\n";
			$retVal .= $SELF_DS ."-DS$i rdf:type void:Linkset ;\n";
			$retVal .= " void:linkPredicate <$tdsLinkType> ;\n";
			if($dsURI){
				$retVal .= " void:target <$dsURI> ;\n";
			}
			else {
				$retVal .= " void:target $SELF_DS ;\n";
			}
			$retVal .= " void:target :DS$i .\n";
			$i++;
		}
	}

	return $retVal;
}

function validateHTTPURI($URI){
	$ret = "";
	$c = curl_init();
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_HEADER, 0);
	curl_setopt($c, CURLOPT_URL, $URI);
	curl_setopt($c,	CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($c, CURLOPT_TIMEOUT, 30);
	curl_exec($c);
	if(!curl_errno($c)) {
		$info = curl_getinfo($c);
		if($info['http_code'] == "200") $ret = "valid";
		else $ret = "non-valid";
	}
	else {
		 $ret = "error";
	}
	curl_close($c);
	return $ret;
}

// see http://lookup.dbpedia.org/api/search.asmx?op=KeywordSearch
function lookupSubjectInDBPedia($keyword){
	global $BASE_DBPEDIA_LOOKUP_URI;
	global $DEBUG;
	
	$matches = array();
	
	$data = file_get_contents($BASE_DBPEDIA_LOOKUP_URI . $keyword);
	$parser = xml_parser_create();
	xml_parse_into_struct($parser, $data, $values);
	xml_parser_free($parser);
	for( $i=0; $i < count($values); $i++ ){
		$match = array();
		
		if($values[$i]['tag'] == strtoupper("Description")) {
			$desc =  $values[$i]['value'];
		}
		if($values[$i]['tag']==strtoupper("Label")){
			$label =  $values[$i]['value'];
		}
		if($values[$i]['tag']==strtoupper("URI") && 
			(strpos($values[$i]['value'], "http://dbpedia.org/resource") == 0) &&
			(strpos($values[$i]['value'], "Category:") === false)
		){ // use only resource URIs and exclude category resource URIs
			$URI =  $values[$i]['value'];
		}
		
		if(isset($URI) && isset($desc)&& isset($label)) {
			$match['URI'] = $URI;
			$match['label'] = $label;
			$match['desc'] = $desc;
			
			array_push($matches, $match);
			if($DEBUG) {
				echo "<strong>" . $URI . "</strong>:<p>" . $desc . "</p>" ;
			}
			unset($URI);
			unset($desc);
			unset($label);
		}
	}
	return json_encode($matches);
}

function lookupVoiD($lookupURI, $browseURI, $homepageURI){
	global $DEBUG;
	global $BASE_RKB_LOOKUP_URI;
	
	//NOTE: this should be the same for all store, but due to http://void.rkbexplorer.com/known-limitations/ we have to make a case distinction
	if($lookupURI == $BASE_RKB_LOOKUP_URI ){ // treat RKB special, ie exact match
		$query = "SELECT DISTINCT ?ds WHERE { ?ds a <http://rdfs.org/ns/void#Dataset> ; <http://xmlns.com/foaf/0.1/homepage> <$homepageURI> . }";
	}
	else { // do partial match
		$query = "SELECT DISTINCT ?ds WHERE { ?ds a <http://rdfs.org/ns/void#Dataset> ; <http://xmlns.com/foaf/0.1/homepage> ?hp . FILTER regex(str(?hp), \"$homepageURI\") . }";	
	}
	
	if($DEBUG) echo $query . "<br />\n";
	
	$jsondata = file_get_contents($lookupURI . urlencode($query));
	if($DEBUG) var_dump($jsondata);
	
	$data = json_decode($jsondata, true); 
	
	if($DEBUG) var_dump($data["results"]["bindings"][0]["ds"]["value"]);
	
	$val = $data["results"]["bindings"][0]["ds"]["value"];
	$type = $data["results"]["bindings"][0]["ds"]["type"];
	
	if($type == "uri") $val = urlencode($val);
	
	$browseParams = "type=$type&uri=$val";
	
	return $browseURI . $browseParams;
}

function listVoiD($lookupURI){
	global $DEBUG;
	
	$voiDInfoList = array();
	
	$query = "SELECT DISTINCT ?ds ?title ?hp WHERE { ?ds a <http://rdfs.org/ns/void#Dataset> ;  <http://purl.org/dc/terms/title> ?title ; <http://xmlns.com/foaf/0.1/homepage> ?hp . } ORDER BY ?title ";
	if($DEBUG) echo $query . "<br />\n";
	
	$jsondata = file_get_contents($lookupURI . urlencode($query));
	if($DEBUG) var_dump($jsondata);
	
	$data = json_decode($jsondata, true); 
	
	foreach($data["results"]["bindings"] as $binding){
		$voiDInfo = array();
		$id = $binding["ds"]["value"];		
		$title = $binding["title"]["value"];
		$homepage = $binding["hp"]["value"];
		if($DEBUG) echo "<a href='$homepage' target='_new' title='$homepage'>$title</a> (in dataset $id)<br />\n";
		$voiDInfo['id'] = $id;
		$voiDInfo['title'] = $title;
		$voiDInfo['homepage'] = $homepage;
		array_push($voiDInfoList, $voiDInfo);
	}
	
	return json_encode($voiDInfoList);
}

function inspectVoiDLive($voiDInTTL){
	$webInspectorServiceURI = "http://apps.sindice.net:8080/rdfextractor/rdfextract";
	$fields = array(
	        'content'=>urlencode($voiDInTTL),
	        'format'=> "turtle"
	);

	foreach($fields as $key=>$value) {
		$fields_string .= $key.'='.$value.'&';
	}
	rtrim($fields_string,'&');
	
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$webInspectorServiceURI);
	curl_setopt($ch,CURLOPT_POST,count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
	//execute post
	$result = curl_exec($ch);
	//close connection
	curl_close($ch);
	return $result;
}

function inspectVoiD($voiDInTTL){
	$webInspectorServiceURI = "http://sindice.com/developers/inspector?url=";
	$tmpVoiDURI = dumpVoid($voiDInTTL);
	return $webInspectorServiceURI . urlencode($tmpVoiDURI );
}

function dumpVoid($voiDInTTL){
	$search  = array(' ', '.');
	$replace = array('', '');
	$tmpVoiDFileName =  "tmp/void_" . microtime();
	$tmpVoiDFileName =  str_replace ($search, $replace, $tmpVoiDFileName) . ".ttl";
	
	$fh = fopen($tmpVoiDFileName, 'a') or die("Can't open temp voiD file ...");
	fwrite($fh, $voiDInTTL);
	fclose($fh);
	return "http://" . $_SERVER["HTTP_HOST"] . "/ve2/" . $tmpVoiDFileName;
}

function lookupPrefix($prefix){
	$ret = "";
	$c = curl_init();
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_HEADER, 0);
	curl_setopt($c, CURLOPT_URL, "http://prefix.cc/" . strtolower($prefix) . ".json.plain");
	curl_setopt($c, CURLOPT_TIMEOUT, 30);
	$result = curl_exec($c);
	curl_close($c);
	$result = json_decode($result, true);
	return $result[strtolower($prefix)];
}

// ping a service that supports  http://hixie.ch/specs/pingback/pingback
function pingback($servicetitle, $endpoint, $title, $URI) {
	global $DEBUG;
	
	$client = new xmlrpc_client($endpoint);
	$payload = new xmlrpcmsg("weblogUpdates.ping");
	
	$payload->addParam(new xmlrpcval($title));
	$payload->addParam(new xmlrpcval($URI));
	
	if ($DEBUG) {
		$client->setDebug(2);
	}
	
	$response = $client->send($payload);
	$xmlresponsestr = $response->serialize();
	
	$xml = simplexml_load_string($xmlresponsestr);
	$result = $xml->xpath("//value/boolean/text()");
 	if($result) {
		if($result[0] == "0"){
			return "<p>Submitting $URI to $servicetitle succeeded.</p>";
		}
	}
	else {
		$err = "Error Code: " . $response->faultCode() . "<br /> Error Message: " . $response->faultString();
		return "<p>Failed to submit $URI to $servicetitle.</p>";
	}
}

// ping a service with RESTful API
function ping($servicetitle, $endpoint, $queryParam, $URI){
	$fields = array(
	        $queryParam =>urlencode($URI)
	);
	foreach($fields as $key=>$value) {
		$fields_string .= $key.'='.$value.'&';
	}
	rtrim($fields_string,'&');

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch,CURLOPT_URL,$endpoint);
	curl_setopt($ch,CURLOPT_POST,count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
	$result = curl_exec($ch);	
	if(!curl_errno($ch)) {
		$info = curl_getinfo($ch);
		if($info['http_code'] == "200") {
			return "<p>Submitting $URI to $servicetitle succeeded.</p>";
		}
		else {
			return "<p>Failed to submit $URI to $servicetitle.</p>";
		}
	}
	else {
		 return "<p>Failed to submit $URI to $servicetitle.</p>";
	}
	curl_close($ch);
	return $ret;
}


?>