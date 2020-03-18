<?php

ini_set('memory_limit', '128M');

class socketBlock extends Exception {};
class socketTimeout extends Exception {};

/**
 * Class clientTimeBase
 * Defines abstract class of attendance terminal driver
 */
abstract class clientTimeBase
{
    /**
     * @var string
     */
      protected $errcode;

    /**
     * @var string
     */
      protected $status;

    /**
     * @var string
     */
      protected $lastError;

      protected $sk, $srv_ip, $buff, $blockSnd, $listResponses;
      public $campsRes;

    /**
     * Last response of communications from terminal
     * @return mixed
     */
      public function lastResponse() 
      {
               return $this->buff;
      }

    /**
     * Return a block response. Is a set of responses
     * @return mixed
     */
      public function blockResponse() 
      {
               return $this->listResponses;
      }

    /**
     * Return last error code of command execution
     * @return string
     */
      public function getErrorCode()
      {
               return $this->errcode;
      }

    /**
     * Return last status command execution
     * @return string
     */
      public function getStatus()
      {
               return $this->status;
      }

    /**
     * @return string
     */
      public function getLastError()
      {
          return $this->lastError;
      }
      
    /**
     * Sends command to terminal
     * @param string $type Type of command
     * @param string $cmd Command name
     * @param mixed $l_args Argument list of command
     * @return mixed
     */
      abstract public function sendCommand($type, $cmd, $l_args=null);

    /**
     * Clear previous commands block
     * @return mixed
     */
      abstract public function clearCommandsBlock();

    /**
     * Add command into block
     * @param string $cmd Command name
     * @param mixed $l_args Argument list of command
     * @return mixed
     */
      abstract public function addCommandToBlock($cmd, $l_args=null);

    /**
     * Send defined command block
     * @return mixed
     */
      abstract public function sendCommandsBlock();

    /**
     * Open connection to terminal
     * @return mixed
     */
      abstract public function open();

    /**
     * Close connection
     * @return mixed
     */
      abstract public function close();
}