<?php

/**
 * Class AppInput
 *
 * Covers the input from the operating system - console and file.
 */
class AppInput
{
    /**
     * Get content of the file.
     *
     * @param $fileName
     * @return string
     *
     * @throws InvalidQueryException
     */
    public function getFileContent($fileName)
    {
        $data = null;

        if (is_file($fileName)) {
            $data = file_get_contents($fileName);
        } else {
            if (is_file(__DIR__.'/'.$fileName)) {
                $data = file_get_contents(__DIR__.'/'.$fileName);
            } else {
                //todo: test absolute and relative urls: /file, file, .file, ./file
                //throw new InputFileException('N')
            }
        }

        if (!$data) {
            throw new InvalidQueryException('Could not read a query from the file: '.$fileName);
        }

        return $data;
    }

    /**
     * Check whether the file exists or not.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function fileExists($fileName)
    {
        //todo: refactor to a php method?

        if (is_file($fileName)) {
            return true;
        } else {
            if (is_file(__DIR__.'/'.$fileName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function readFromStdin()
    {
        return file_get_contents('php://stdin');
    }
}
