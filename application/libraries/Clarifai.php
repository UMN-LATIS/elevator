<?php

class Clarifai {


	private $clientId = null;
 	private $clientSecret = null;
 	private $endPoint = "https://api.clarifai.com/";
 	private $collectionTitle;

	public function __construct($clientId=null, $clientSecret=null, $collectionTitle=null)
	{
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
		$this->collectionTitle = $collectionTitle;

	}


	public function getAccessToken() {

		$client = new GuzzleHttp\Client(['base_url' => $this->endPoint]);

		$request = $client->createRequest("POST", "/v1/token");
		$postBody = $request->getBody();
		$postBody->setField("grant_type", "client_credentials");
		$postBody->setField("client_id", $this->clientId);
		$postBody->setField("client_secret", $this->clientSecret);

		try {
    		$response = $client->send($request)->json();
		} catch (HttpException $ex) {
			echo $ex;
			return;
		}

		if($response["access_token"]) {
			$this->accessToken = $response["access_token"];
			return true;
		}

		return false;

	}


	public function getCollections() {

		$response = $this->requestWithBody("get", "/v1/curator/collections", array());
		return $response;

	}

	public function collectionExists($collectionName) {
		$collections = $this->getCollections();

		foreach($collections->collections as $collection) {
			if($collection->id == $collectionName) {
				return true;
			}
		}

		return false;
	}

	public function addCollection($collectionTitle = null) {

		if(!$collectionTitle) {
			return false;
		}

		$this->collectionTitle = $collectionTitle;

		$bodyArray["id"] = $collectionTitle;
		$bodyArray["settings"] = ["max_num_docs"=>10000000];
		$bodyArray["properties"] = ["title"=>["type"=>"string"], "tags"=>["type"=>"string"]];
		$submissionArray["collection"] = $bodyArray;

		$response = $this->requestWithBody("post", "/v1/curator/collections", $submissionArray);
		return $response;
	}


	public function deleteCollection($collectionTitle) {

		$response = $this->requestWithBody("delete", "/v1/curator/collections/" . $collectionTitle, array());
		return $response;

	}

	public function addDocument($imagePath, $docId) {

		$bodyArray["media_refs"][] = ["url"=>$imagePath, "media_type"=>"image"];
		$bodyArray["docid"] = $docId;
		$submitArray["document"]=$bodyArray;
		$submitArray["options"] = ["want_doc_response"=>true, "recognition_options"=>["model"=>"default"]];

        $response = $this->requestWithBody("post", "/v1/curator/collections/" . $this->collectionTitle. "/documents", $submitArray);
        return $response;

	}

	public function getDocument($docId) {
		$response = $this->requestWithBody("get", "/v1/curator/collections/" . $this->collectionTitle. "/documents/" . $docId, array());
		return $response;
	}

	public function addAnnotations($docId, $tagArray) {
		$putBody['namespace'] = "my_tags";
		foreach($tagArray as $tag) {
			$putBody['annotations'][] = ["tag"=>["cname"=>$tag], "score"=>1.0];
		}

		$submissionArray['annotation_set'] = $putBody;

		$response = $this->requestWithBody("put", "/v1/curator/collections/" . $this->collectionTitle. "/documents/" . $docId . "/annotations", $submissionArray);
		return $response;

	}

	public function search($docId) {
		$bodyArray["bool"]= ["should"=>[["similar_items"=>$docId]]];
		$submissionArray["query"] = $bodyArray;
		$submissionArray["num"] = 10;

		$response = $this->requestWithBody("post", "/v1/curator/collections/" . $this->collectionTitle. "/search", $submissionArray);
        return $response;

	}

	public function clientForUrl($requestType, $address) {
		if(!$this->getAccessToken()) {
			return false;
		}

		$client = new GuzzleHttp\Client(['base_url' => $this->endPoint]);

		switch ($requestType) {
			case 'get':
				$request = $client->createRequest("get", $address);
				break;
			case 'post':
				$request = $client->createRequest("post", $address);
				break;
			case 'put':
				$request = $client->createRequest("put", $address);
				break;
			case 'delete':
				$request = $client->createRequest("delete", $address);
				break;
			default:
				$request = new Object;
				break;
		}
		$request->addHeader("Authorization", "Bearer " . $this->accessToken);

		return [$client, $request];


	}

	private function requestWithBody($requestType, $address, $body) {

		list($client, $request) = $this->clientForUrl($requestType, $address);
		if(!$request) {
			return false;
		}

		if($body) {
			$request->setBody(GuzzleHttp\Stream\Stream::factory(json_encode($body, JSON_UNESCAPED_SLASHES)));
			$request->setHeader("Content-Type", 'application/json');
		}


		try {
    		$response = $this->hackyJsonDecode($client->send($request)->getBody());

		} catch (ClientErrorResponseException $ex) {
			echo $ex->getResponse()->getBody(true);
			return;
		}
		catch (Guzzle\Http\Exception\ServerErrorResponseException $e) {

            $req = $e->getMessage();
            echo (string) $e->getResponse()->getBody();

        }

        return $response;


	}

	/**
	 * oh ubuntu..
	 */
	public function hackyJsonDecode($body) {
		// if (version_compare(PHP_VERSION, '5.4.0', '>=') && !(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
		//     * In PHP >=5.4.0, json_decode() accepts an options parameter, that allows you
		//      * to specify that large ints (like Steam Transaction IDs) should be treated as
		//      * strings, rather than the PHP default behaviour of converting them to floats.



		//     $obj = json_decode($body, false, 512, JSON_BIGINT_AS_STRING);
		// } else {
		//     /** Not all servers will support that, however, so for older versions we must
		//      * manually detect large ints in the JSON string and quote them (thus converting
		//      *them to strings) before decoding, hence the preg_replace() call.
		//      */
		    $max_int_length = strlen((string) PHP_INT_MAX) - 1;
		    $json_without_bigints = preg_replace('/:\s*(-?\d{'.$max_int_length.',})/', ': "$1"', $body);
		    $obj = json_decode($json_without_bigints);
		// }

		return $obj;
	}



}
