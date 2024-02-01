<?php
// define("TOOL_HOST", "https://sith.knowfear.net/lti/src/web");
use Packback\Lti1p3\Interfaces\IDatabase;
use Packback\Lti1p3\LtiRegistration;
use Packback\Lti1p3\LtiDeployment;
use Packback\Lti1p3\OidcException;


class LTI13Database implements IDatabase {
    
      public static function findIssuer($issuer_url, $client_id = null)
    {
        $CI =& get_instance();

        $lookupQuery = ["host"=>$issuer_url];
        

        if($client_id) {
            $lookupQuery["client_id"] = $client_id;
        }

        $result = $CI->doctrine->em->getRepository("Entity\LTI13Issuer")->findBy($lookupQuery);
        if (count($result) > 1) {
            throw new OidcException('Found multiple registrations for the given issuer, ensure a client_id is specified on login (contact your LMS administrator)', 1);
        }
        
        return $result[0] ?? null;
    }

    public function findRegistrationByIssuer($issuer_id, $client_id=null) {

        $issuer = self::findIssuer($issuer_id, $client_id);
        if (!$issuer) {
            return false;
        }

        return LtiRegistration::new()
            ->setAuthLoginUrl($issuer->getAuthLoginUrl())
            ->setAuthTokenUrl($issuer->getAuthTokenUrl())
            // ->set_auth_server($_SESSION['iss'][$iss]['auth_server'])
            ->setClientId($issuer->getClientId())
            ->setKeySetUrl($issuer->getKeySetUrl())
            ->setKid($issuer->getKid())
            ->setIssuer($issuer_id)
            ->setToolPrivateKey($issuer->getPrivateKey());
    }

    public function findDeployment($iss, $deployment_id, $client_id = null) {
        $CI =& get_instance();
        $deployment = $CI->doctrine->em->getRepository("Entity\LTI13Deployment")->findOneBy(["deployment_id"=>$deployment_id]);
        if($deployment) {
            return LtiDeployment::new()
                ->setDeploymentId($deployment->getDeploymentId());
        } else {
            $deployment = new Entity\LTI13Deployment();
            $deployment->setDeploymentId($deployment_id);
            $issuer = self::findIssuer($iss, $client_id);
            $deployment->setIssuer($issuer);
            $CI->doctrine->em->persist($deployment);
            $CI->doctrine->em->flush();
            return LtiDeployment::new()
                ->setDeploymentId($deployment->getDeploymentId());
        }
        
    }

  
}
?>