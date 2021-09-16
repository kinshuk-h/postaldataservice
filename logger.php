<?php

class FileLogger {
    private $filename;

    private function log_to_file($message) {
        file_put_contents($this->filename, $message . "\n", FILE_APPEND);
    }
    private function log_level($timestamp, $level) {
        $this->log_to_file(strftime("%Y-%m-%d %H:%M:%S", $timestamp) . " " . $level);
    }

    public function __construct($filename) { $this->filename = $filename; }
    public function info ($message) { $this->log_level(time(), "INFO" ); $this->log_to_file($message); }
    public function error($message) { $this->log_level(time(), "ERROR"); $this->log_to_file($message); }
}

?>