<?php

/**
 * Class AppInput
 */
class AppInput
{
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
     * @param $fileName
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

    public function readFromStdin()
    {
        return file_get_contents('php://stdin');
    }
}
