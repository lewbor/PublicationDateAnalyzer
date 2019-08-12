<?php


namespace App\Lib;


use Exception;
use RuntimeException;

class CsvWriter
{
    protected $filename;
    protected $delimiter;
    protected $enclosure;
    protected $escape;
    protected $file;
    protected $showHeaders;
    protected $position;
    protected $withBom;

    /**
     * @param string $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param bool   $showHeaders
     * @param bool   $withBom
     */
    public function __construct(
        $filename,
        $delimiter = ',',
        $enclosure = '"',
        $escape = '\\',
        $showHeaders = true,
        $withBom = false)
    {
        $this->filename    = $filename;
        $this->delimiter   = $delimiter;
        $this->enclosure   = $enclosure;
        $this->escape      = $escape;
        $this->showHeaders = $showHeaders;
        $this->position    = 0;
        $this->withBom     = $withBom;

        if (is_file($filename)) {
            throw new RuntimeException(sprintf('The file %s already exist', $filename));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->file = fopen($this->filename, 'w', false);
        if (true === $this->withBom) {
            fprintf($this->file, chr(0xEF).chr(0xBB).chr(0xBF));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        fclose($this->file);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        if ($this->position == 0 && $this->showHeaders) {
            $this->addHeaders($data);

            ++$this->position;
        }

        $result = @fputcsv($this->file, $data, $this->delimiter, $this->enclosure);

        if (!$result) {
            throw new Exception();
        }

        ++$this->position;
    }

    /**
     * @param array $data
     */
    protected function addHeaders(array $data)
    {
        $headers = array();
        foreach ($data as $header => $value) {
            $headers[] = $header;
        }

        fputcsv($this->file, $headers, $this->delimiter, $this->enclosure);
    }
}