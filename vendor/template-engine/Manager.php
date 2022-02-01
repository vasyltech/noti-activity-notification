<?php

namespace Noti\Vendor\TemplateEngine;

/**
 * Origin - https://codeshack.io/lightweight-template-engine-php/
 */
class Manager
{

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private static $_instance = null;

    /**
     * Undocumented variable
     *
     * @var array
     */
    private $_options = [];

    /**
     * Undocumented function
     *
     * @param [type] $options
     */
    protected function __construct($options)
    {
        $this->_options = array_merge([
            'temp_dir' => sys_get_temp_dir()
        ], $options);
    }

    /**
     * Undocumented function
     *
     * @param [type] $template
     * @param array $data
     * @return void
     */
    public function render($template, $data = [])
    {
        $cached_file = $this->compileTemplate($template);

        ob_start();
        extract($data, EXTR_SKIP);
        require $cached_file;
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Undocumented function
     *
     * @param [type] $template
     * @return void
     */
    protected function compileTemplate($template)
    {
        $hash     = md5($template);
        $filename = $this->_options['temp_dir'] . '/' . $hash . '.php';

        if (!file_exists($filename)) {
            $header = '<?php class_exists("' . __CLASS__ . '") or exit; ?>' . PHP_EOL;

            $code = $header . $this->compileEscapedEchos($template);
            $code = $this->compileEchos($code);
            $code = $this->compilePHP($code);

            file_put_contents($filename, $code);
        }

        return $filename;
    }

    /**
     * Undocumented function
     *
     * @param [type] $code
     * @return void
     */
    protected function compilePHP($code)
    {
        return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
    }

    /**
     * Undocumented function
     *
     * @param [type] $code
     * @return void
     */
    protected function compileEchos($code)
    {
        return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo $1 ?>', $code);
    }

    /**
     * Undocumented function
     *
     * @param [type] $code
     * @return void
     */
    protected function compileEscapedEchos($code)
    {
        return preg_replace(
            '~\{{{\s*(.+?)\s*\}}}~is',
            '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>',
            $code
        );
    }

    /**
     * Undocumented function
     *
     * @param array $options
     * @param boolean $reinit
     *
     * @return Manager
     */
    public static function getInstance($options = [], $reinit = false)
    {
        if (is_null(self::$_instance) || $reinit) {
            self::$_instance = new self($options);
        }

        return self::$_instance;
    }

}