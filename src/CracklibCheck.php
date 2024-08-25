<?php

namespace KrisRo\Validator;

class CracklibCheck {
  
  protected $descriptorSpec;
  protected $process;
  protected $pipes;
  protected $result;
  protected $response;
  
  protected $command = '/usr/sbin/cracklib-check';
  
  public function __construct(?string $command = NULL) {
    if (!function_exists('proc_open')) {
      trigger_error('Can not run cracklib-check. Make sure cracklib-runtime is available on your system and proc_open is usable.', E_USER_ERROR);
    }

    if ($command) {
      $this->command = $command;
    }
    
    $this->descriptorSpec = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w'],
    ];
    
    $this->getProcess();
  }
  
  public function testPassword(string $value): bool {
    if (!is_resource($this->process)) {
      trigger_error('Can not run cracklib-check. Make sure cracklib-runtime is available on your system and proc_open is usable.', E_USER_ERROR);
      return FALSE;
    }
    
    fwrite($this->pipes[0], $value);
    fclose($this->pipes[0]);
    
    $this->result = stream_get_contents($this->pipes[1]);
    fclose($this->pipes[1]);

    $return = proc_close($this->process);

    if ($return === -1) {
      trigger_error('Cracklib-check execution error');
      return FALSE;
    }
    
    return $this->processResponse();
  }
  
  public function getResponseMessage(): string {
    return $this->response ?? '';
  }
  
  protected function getProcess(): self {
    $this->process = proc_open($this->command, $this->descriptorSpec, $this->pipes);
    return $this;
  }
  
  protected function processResponse(): bool {
    if (!$this->result) {
      trigger_error('Can not run cracklib-check. Make sure cracklib-runtime is available on your system and proc_open is usable.', E_USER_ERROR);
      return FALSE;
    }
    
    $this->result = explode(':', $this->result);
    
    $this->response = trim($this->result[1]);
    
    return strtoupper($this->response) === 'OK';
  }
}