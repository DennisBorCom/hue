<?php

    // created by Dennis Bor (dennis@dennisbor.com)
    // download at https://github.com/DennisBorCom/hue

    // to obtain a hue api key, follow these steps:
    //
    // go to https://discovery.meethue.com/ to get a JSON-result containing your 
    // bridge local ip address.
    //
    // e.g.: {"id":"001788fffea44e06","internalipaddress":"192.168.1.244","port":443}
    //
    // go to https://<internalipaddress>/debug/clip.html to open the Hue API debugger.
    // replace <internalipaddress> with the address obtained in the JSON-result.
    //
    // press the link button on your Hue Bridge.
    //
    // send a POST-request to URL /api containing the following message body:
    //
    // {"devicetype":"<name_of_app>"}
    //
    // replace <name_of_app> with the desired app (or user) name.
    //
    // you should get the following result:
    //
    // [ { "success": { "username": "<token>" } } ]
    //
    // use the obtained token for authorization.

    class Hue {

        private $token;
        private $ip;
        
        private $lights;
   
        function __construct(string $ip, string $token) {
            $this->ip = $ip;   
            $this->token = $token;
            $this->getLightInformation();
        }

        private function getLightInformation() : bool {
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://" . $this->ip . "/api/" . $this->token . "/lights");
            curl_setopt($cURLResource, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($cURLResource, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            if (($output = curl_exec($cURLResource)) === false) {
                return false;
            }
            curl_close($cURLResource);   
            $this->lights = (array) json_decode($output);
            return true;
        }

        public function getLights() : array {
            return $this->lights;
        }

        public function getLightByName(string $name) : object {
            foreach ($this->lights as $light) {
                if (strtoupper($light->name) == strtoupper($name)) {
                    return $light;
                }
            }
            throw new Exception("Hue: Invalid light name '" . $name . "'");
        }
     
        private function getLightIndex(object $light) : int {
            
            foreach ($this->lights as $key => $currentLight) {
                if ($light->name == $currentLight->name) {
                    return $key;
                }
            }
            throw new Exception("Hue: Invalid light object");
        }

        public function toggleOff(object $light) : void {
            $this->toggleLight($light, false);
        }

        public function toggleOn(object $light) : void {
            $this->toggleLight($light, true);
        }

        private function toggleLight(object $light, bool $toggleOn) : void {
            $json = array();
            $json['on'] = $toggleOn;
            $data = json_encode($json, JSON_NUMERIC_CHECK);
            $cURLResource = curl_init();
            curl_setopt($cURLResource, CURLOPT_URL, "https://" . $this->ip . "/api/" . $this->token . "/lights/" . $this->getLightIndex($light) . "/state");
            curl_setopt($cURLResource, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($cURLResource, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($cURLResource, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($cURLResource, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($cURLResource, CURLOPT_POSTFIELDS, $data);
            if (($output = curl_exec($cURLResource)) === false) {
                return;
            }
            curl_close($cURLResource);   
            return;
        }

        public function isOn(object $light) : bool {
            if (!isset($light->state->on)) {
                throw new Exception('Hue: Invalid light object');
            }
            return $light->state->on; 
        }
    }
?>