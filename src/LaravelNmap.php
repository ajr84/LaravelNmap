<?php
namespace LaravelNmap;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class LaravelNmap
{
        /*
         * used system default nmap command.
         * If you want to change to specific binary,
         * you can use like '/usr/bin/nmap'
         */
        const CMD = 'nmap';
        private $process;
        private $arguments = [];
        private $options = [];
        private $input;
        private $timeout = 300;
        public $result;
        
        public function __construct() {
		$this->process = new ProcessBuilder();
                $this->process->setPrefix(self::CMD);
	}

	public function NmapHelp() {
            /*
             * set argument
             */
            $arguments = ['--help'];
            /*
             * get new Process instance 
             */
            $process = $this->process->setArguments($arguments)
                        ->getProcess();
            /*
             * run the process
             */
            $process->run();
            /*
             * get process output for nmap help
             */
            return $process->getOutput();
	}
        
        public function verbose() {
            /*
             * set argument for verbosity
             */
            $this->arguments[] = '-v';
            return $this;
        }
        
        public function detectOS() {
            /*
             * set argument for OS detection
             */
            $this->arguments[] = '-O';
            return $this;
        }
        
        public function getServices() {
            /*
             * set argument to scan running services
             * if you use this method, use scanPorts() method also to reduce process run time
             */
            $this->arguments[] = '-sV';
            return $this;
        }
        
        public function disablePortScan() {
            /*
             * set argument to disable port scanning
             * Warning: this method should not be used if using '-p' switch somewhere
             */
            $this->arguments[] = '-sn';
            return $this;
        }
        
        /*
         * set ports to scan
         */
        public function scanPorts($ports) {
            $this->arguments[] = '-p'.$ports;
            return $this;
        }
        
        /*
         * set target host or networks seperated by space
         */
        public function target($target) {
            $this->arguments[] = $target;
            return $this;
        }
        
        /*
         * set environment variables
         */
        public function setEnv($name, $value) {
            $this->process->setEnv($name, $value);
            return $this;
        }
        
        /*
         * set process timeout
         */
        public function timeout($timeout) {
            $this->timeout = $timeout;
            $this->process->setTimeout($this->timeout);
            return $this;
        }
        
        /*
         * set current working directory
         */
        public function cwd($cwd) {
            $this->process->setWorkingDirectory($cwd);
            return $this;
        }
        
        /**
         * 
         * @return SimpleXMLElement Object
         */
        public function getXmlObject() {
            $this->arguments[] = '-oX';
            // this argument is needed to get xml output to stdout
            $this->arguments[] = '-';
            
            $process = $this->process->setArguments($this->arguments)->getProcess();
            
            $process->run();
            
            $xmldata = $process->getOutput();
            return simplexml_load_string($xmldata);             
        }
        
        public function getArray() {
            $xml = $this->getXmlObject();
            $array = [];
            foreach($xml->host as $host) {
                $addr = (string) $host->address->attributes()->addr;
                $array[$addr]['addr'] = $addr;
                $array[$addr]['type'] = (string) $host->address->attributes()->addrtype;
                $array[$addr]['state'] = (string) $host->status->attributes()->state;
                if(isset($host->hostnames)) {
                    $array[$addr]['hostname'] = call_user_func_array('array_merge',(array)$host->hostnames->hostname);
                }
                if(isset($host->ports)) {
                    $array[$addr]['ports'] = $this->getPorts($host->ports->port);
                }
            }
            return $array;
        }

        public function getRawOutput() {
            $process = $this->process->setArguments($this->arguments)->getProcess();
            
            $process->run();
            
            return $process->getOutput();
        }
        
        private function getPorts(\SimpleXMLElement $xmlPorts) {
            $ports = [];
            foreach($xmlPorts as $port) {
                $portid = (string) $port->attributes()->portid;
                $ports[$portid]['protocol'] = (string) $port->attributes()->protocol;
                $ports[$portid]['state'] = (string) $port->state->attributes()->state;
                $ports[$portid]['service'] = (string) $port->service->attributes()->name;
            }
            return $ports;
        }
}