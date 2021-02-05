<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * this is for a future releasel
 */


/**
 * Breaking this out as a separate controller because, for the moment at least, we're using
 * mostly sample code from the mule-uploader project.  I don't want to mix that in with our existing code.
 */
class uploadBackend extends Instance_Controller {

    public $MIME_TYPE = "application/octet-stream";
    public $BUCKET;
    public $AWS_SECRET;
    public $AWS_ACCESS_KEY;
    public $DEBUG = true;
    public $ENGINE;
    public $CHUNK_SIZE = 6*1024*1024;  // 6MB

    public function __construct() {
        parent::__construct();
    }



    public function upload($action) {

        $collectionId = $_GET['collectionId'];
        $collection = $this->collection_model->getCollection($collectionId);
        if(isset($_GET['mime_type'])) {
            $this->MIME_TYPE = $_GET['mime_type'];
        }
        $this->BUCKET = $collection->getBucket();
        $this->AWS_SECRET = $collection->getS3Secret();
        $this->AWS_ACCESS_KEY = $collection->getS3Key();
        $this->AWS_REGION = $collection->getBucketRegion();
        echo $this->upload_action($action);
    }

    private function sign($key, $message, $rawOutput) {

        return hash_hmac('sha256', $message, $key, $rawOutput);
    }

    private function getSignatureWithKey($key, $dateStamp, $region, $service) {

        $k_date = $this->sign("AWS4" . $key, $dateStamp, true);
        $k_region = $this->sign($k_date, $region, true);
        $k_service = $this->sign($k_region, $service, true);
        $k_signing = $this->sign($k_service, "aws4_request", false);
        return $k_signing;

    }


    public function getSignature($date) {
        return $this->getSignatureWithKey($this->AWS_SECRET, $date->format("Ymd"), $this->AWS_REGION, "s3");

    }


    public function upload_action($action) {
        if(isset($_GET['key'])) {
            $uploadkey = $_GET['key'];
        }
        else {
            $uploadkey = "";
        }
        if(isset($_GET['upload_id'])) {
            $upload_id = $_GET['upload_id'];
        }
        if(isset($_GET['chunk'])) {
            $chunk = $_GET['chunk'];
        }
        $string = $date = NULL;


        if($action == 'chunk_loaded') {
            $filename = $_GET['filename'];
            $filesize = (int)$_GET['filesize'];
            $last_modified = $_GET['last_modified'];
            $chunk = (int)$_GET['chunk'];

            if($filesize > $this->CHUNK_SIZE) {
                $upload = $this->doctrine->em->getRepository("Entity\Uploads")->findOneBy(['filename' => $filename, "filesize"=>$filesize,"last_modified"=>$last_modified, "uploadKey"=>$uploadkey]);
                if($upload) {
                    $chunks = explode(',', $upload->getChunksUploaded());
                    $chunks[] = $chunk;
                    $chunks = implode(',', array_unique($chunks));

                    //$upload = $this->doctrine->em->find('Entity\Uploads', $upload->getUploadId());
                    $upload->setChunksUploaded($chunks);
                    $this->doctrine->em->persist($upload);
                    $this->doctrine->em->flush($upload);
                } else {
                    $upload = new Entity\Uploads();
                    $upload->setFilename($filename);
                    $upload->setFilesize($filesize);
                    $upload->setLastModified($last_modified);
                    $upload->setChunksUploaded($chunk);
                    $upload->setUploadKey($uploadkey);
                    $upload->setUploadId($upload_id);
                    $this->doctrine->em->persist($upload);
                    $this->doctrine->em->flush($upload);
                }
            }

            return '';
        }

        $nowUtc = new \DateTime( 'now',  new \DateTimeZone( 'UTC' ) );

        if($action == 'signing_key') {
            $key = $this->getSignature($nowUtc);
            $filename = $_GET['filename'];
            $filesize = (int)$_GET['filesize'];
            $last_modified = $_GET['last_modified'];
            $this->logging->logError("payload", $_GET);
            $outputData = [
                    "date" => $nowUtc->format("Y-m-d\TH:i:s"),
                    "signature" => $key,
                    "access_key" => $this->AWS_ACCESS_KEY,
                    "region" => $this->AWS_REGION,
                    "bucket" => $this->BUCKET,
                    "backup_key" => strval(rand(1, 1000000)),
                    "content_type" => $this->MIME_TYPE,
                ];

            $force = isset($_GET['force'])?$_GET['force']:false;

            $upload = $this->doctrine->em->getRepository("Entity\Uploads")->findOneBy(array('filename' => $filename, "filesize"=>$filesize,"last_modified"=>$last_modified,  "uploadKey"=>$uploadkey));

            if($upload && !$force) {
                $this->logging->logError("Resume!");
                $chunks = explode(',', $upload->getChunksUploaded());
                $outputData["key"] = $upload->getUploadKey();
                $outputData["upload_id"] = $upload->getUploadId();
                $outputData["chunks"] = $chunks;
            } else {
                $upload = $this->doctrine->em->getRepository("Entity\Uploads")->findOneBy(array('filename' => $filename, "filesize"=>$filesize,"last_modified"=>$last_modified,  "uploadKey"=>$uploadkey));
                if($upload) {
                    $this->doctrine->em->remove($upload);
                    $this->doctrine->em->flush();
                }

            }

            return json_encode($outputData, JSON_UNESCAPED_SLASHES);
        }


    }

}

/* End of file  */
/* Location: ./application/controllers/ */