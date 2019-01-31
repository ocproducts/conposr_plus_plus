<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

class Logger
{
    static protected function getInstance()
    {
        static $instance;
        if ($instance === null) {
            $instance = new Logger();
        }

        return $instance;
    }

    // ---

    protected $logFile;

    public function __construct()
    {
        $filename = strftime('%Y-%m-%d.log', time());
        $this->logFile = fopen(get_file_base() . '/_logs/' . $filename, 'ab');
    }

    public function __destruct()
    {
        if ($this->logFile !== null) {
            fclose($this->logFile);
            $this->logFile = null;
        }
    }

    protected function enterLogLine($typeLabel, $msg)
    {
        $logPrefixParts = array(
            DateTimeUtil::formatDateTime(new DateTime()),
            $typeLabel,
            get_ip_address(),
            'User #' . get_member(),
            get_member_email_address(get_member()),
        );
        $logLine = implode(' - ', $logPrefixParts) . ': ' . $msg . "\n";
        fwrite($this->logFile, $logLine);

        return $logLine;
    }

    // ---

    static function error($msg)
    {
        $instance = Logger::getInstance();
        $logLine = $instance->enterLogLine('ERROR', $msg);

        if (get_option('dev_mode') == '0') {
            if (function_exists('send_error_mail')) {
                send_error_mail($logLine);
            }
        }
    }

    static function info($msg)
    {
        $instance = Logger::getInstance();
        $instance->enterLogLine('INFO', $msg);
    }
}
