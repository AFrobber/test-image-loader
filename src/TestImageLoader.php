<?php

namespace afrobber\TestImageLoader;

use Exception;

class TestImageLoader
{
    /** @var string $hashAlgo */
    private $hashAlgo = 'sha1';

    /** @var array $types */
    private static $types = ['image/jpg', 'image/jpeg', 'image/png', 'image/gif'];

    /** @var array $mimeTypes */
    private $mimeTypes = [];

    /** @var array $errors */
    private $errors = [];

    /** @var string $uploadDir */
    private $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR;

    public function __construct($uploadDir = '')
    {
        if($uploadDir) {
            $this->setUploadDir($uploadDir);
        }
        $this->checkUploadDir();
        $this->refreshMimeTypes();
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $mimeType
     * @return $this
     */
    public function addMimeTypes(string $mimeType)
    {
        $this->mimeTypes[] = $mimeType;
        return $this;
    }

    /**
     * Initialisation mime-types default value
     *
     * @return $this
     */
    public function refreshMimeTypes()
    {
        $this->mimeTypes = self::$types;
        return $this;
    }

    /**
     * Set other algorithm for hashing
     * only for registered hashing algorithms
     *
     * @param string $hashAlgo
     * @return $this
     * @throws Exception
     */
    public function setHashAlgo(string $hashAlgo)
    {
        if(!in_array($hashAlgo, hash_algos())) {
            throw new Exception('Hash algorithm ' . $hashAlgo . ' not found.');
        }
        $this->hashAlgo = $hashAlgo;
        return $this;
    }

    /**
     * @param string $uploadDir
     * @return $this
     */
    public function setUploadDir(string $uploadDir)
    {
        $this->uploadDir = $uploadDir;
        $this->checkUploadDir();
        return $this;
    }

    /**
     * Check exist upload directory and has permissions to write
     *
     * @return string
     * @throws Exception
     */
    private function checkUploadDir()
    {
        if(!is_dir($this->uploadDir)) {
            if(!mkdir($this->uploadDir)) {
                throw new Exception('Directory ' . $this->uploadDir . ' was not created!');
            }
        }

        if (substr(sprintf('%o', fileperms($this->uploadDir)), -4) != '0777') {
            if(!chmod($this->uploadDir, 0777)) {
                throw new Exception(
                    'Please, change permission folder "'
                    . $this->uploadDir
                    . '" with CHMOD 0777 in folder you project. Now permission is '
                    . substr(sprintf('%o', fileperms($this->uploadDir)), -4)
                );
            }
        }
    }

    /**
     * @param string $url
     * @return string
     * @throws Exception
     */
    protected function _getSource(string $url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Document ' . $url . ' do not load!' . PHP_EOL . 'Error: ' . curl_error($ch) . PHP_EOL);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 != $httpCode) {
            throw new Exception('Url: ' . $url . '. Response http code: ' . $httpCode);
        }

        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        /*
         * Вполне возможно, некоторые исключительные ситуации, которые есть в этом коде надо оформить в виде ошибок,
         * но это зависит от ресурса, способа работы с ним и целей, преследуемых этим модулем
         * */
        if(!in_array($contentType, $this->mimeTypes)) {
            $this->errors[] = 'File ' . $url . ' mime type ' . $contentType . ' is not supported';
            return false;
        }

        if(!$response) {
            throw new Exception('Blank response.');
        }

        return $response;
    }

    /**
     * @param string $url
     * @return bool
     * @throws Exception
     */
    public function uploadFile(string $url)
    {
        $urlArray = explode('/', $url);

        if(!is_array($urlArray)) {
            throw new Exception('Url ' . $url . ' was not parsed.');
        }

        $filePath = $this->uploadDir . array_pop($urlArray);

        $body = $this->_getSource($url);

        if(!$body) {
            return false;
        }

        if(file_exists($filePath)) {
            $localHash = hash_file($this->hashAlgo, $filePath);
            $remoteHash = hash($this->hashAlgo, $body);
            if(!hash_equals($localHash, $remoteHash)) {
                throw new Exception(
                    'File ' . $filePath . ' is exists with another data.' . PHP_EOL
                    .' Hash local file: ' . $localHash . ', hash remote file: ' . $remoteHash
                );
            }
        }

        file_put_contents($filePath, $body);
        return true;
    }

}
