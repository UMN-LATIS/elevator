<?php

class MY_Router extends CI_Router {

// --------------------------------------------------------------------

/**
 * OVERRIDE
 *
 * Validates the supplied segments.  Attempts to determine the path to
 * the controller.
 *
 * @access    private
 * @param    array
 * @return    array
 */
    function _validate_request($segments) {
        if (count($segments) == 0)
        {
            return $segments;
        }

        if(false == ($segmentsReturn = $this->testSegments($segments))) {
            $this->config->set_item('instance_name', $segments[0]);
            array_shift($segments);
            if(count($segments) == 0) {
                return $segments;
            }

            if(false == ($segmentsReturn = $this->testSegments($segments))) {
                show_404($segments[0]);
            }
            else {
                return $segmentsReturn;
            }
        }
        else {
            return $segmentsReturn;
        }

    }

    function testSegments($segments) {
        // Does the requested controller exist in the root folder?
        if (file_exists(APPPATH.'controllers/'.ucfirst($segments[0]).".php"))
        {
            return $segments;
        }
        elseif (is_dir(APPPATH.'controllers/'.$segments[0]))
        {
            $this->set_directory($segments[0]);
            $segments = array_slice($segments, 1);

            /* ----------- ADDED CODE ------------ */

            while(count($segments) > 0 && is_dir(APPPATH.'controllers/'.$this->directory.$segments[0]))
            {
                // Set the directory and remove it from the segment array
            //$this->set_directory($this->directory . $segments[0]);
            if (substr($this->directory, -1, 1) == '/')
                $this->directory = $this->directory . $segments[0];
            else
                $this->directory = $this->directory . '/' . $segments[0];

            $segments = array_slice($segments, 1);
            }

            if (substr($this->directory, -1, 1) != '/')
                $this->directory = $this->directory . '/';

            /* ----------- END ------------ */

            if (count($segments) > 0)
            {

                if ( ! file_exists(APPPATH.'controllers/'.$this->fetch_directory().'/'.ucfirst($segments[0]).".php"))
                {
                    show_404($this->fetch_directory().$segments[0]);
                }
            }
            else
            {
                $this->set_class($this->default_controller);
                $this->set_method('index');

                if ( ! file_exists(APPPATH.'controllers/'.$this->fetch_directory().'/' .ucfirst($this->default_controller).".php"))
                {
                    $this->directory = '';
                    return array();
                }

            }

            return $segments;
        }
    }
}
