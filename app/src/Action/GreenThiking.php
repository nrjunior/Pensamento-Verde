<?php

namespace App\Action;

use Slim\Http\Request;
use Slim\Http\Response;
use FileSystemCache;
use Thapp\XmlBuilder\XMLBuilder;
use Thapp\XmlBuilder\Normalizer;


final class GreenThiking
{
    private $fileXML;

    public function __invoke(Request $request, Response $response, $args)
    {
       $this->setFileXML(__DIR__ . '/../../../data/pensamento_verde.xml');

        if(file_exists($this->getFileXML()))
        {
            $amount = isset($args['amount']) ? $args['amount'] : 5;
            $forceFileCached = isset($request->getQueryParams()['forceFileCached']) ? $request->getQueryParams()['forceFileCached'] : false;

            FileSystemCache::$cacheDir = __DIR__.'/../../../cache/tmp';
            $key = FileSystemCache::generateCacheKey('cache', null);
            $newXML = FileSystemCache::retrieve($key);

            if($newXML === false || $forceFileCached == true)
            {
                $reader = json_decode(json_encode(simplexml_load_file($this->getFileXML())), true);
                $reader = $reader['item'];
                $newXML = array();

                if (count($reader) < $amount)
                {
                    $amount = count($reader);
                }

                for ($i = 0; $i < $amount; $i++)
                {
                    $indice = rand(0, count($reader) -1);
                    $newXML[$i] = array(
                        'category' => $reader[$indice]['category'],
                        'title' => $reader[$indice]['title'],
                        'text' => $reader[$indice]['text'],
                        'image' => $this->getPathImages() . $reader[$indice]['image']
                    );

                    unset($reader[$indice]);
                    shuffle($reader);
                }

                FileSystemCache::store($key, $newXML, 432000);

            }

        }
        else
        {

            $newXML = array(
                'status' => 'ERROR',
                'message' => 'Arquivo não encontrado'
            );

        }

        $xmlMaker = new XMLBuilder('root');
        $xmlMaker->load($newXML);
        $xml_output = $xmlMaker->createXML(true);
        $response->write($xml_output);
        $response = $response->withHeader('content-type', 'text-html');

        if(isset($newXML['status']) && $newXML['status'] == 'ERROR')
        {
            $response = $response->withStatus(404);

        }
        return $response;


    }


    public function getFileXML()
    {
        return $this->fileXML;
    }


    public function setFileXML($fileXML)
    {
        $this->fileXML = $fileXML;
    }

    public function getPathImages()
    {
        return 'http://'.$_SERVER['HTTP_HOST']. '/data/uploads/images/';
    }

}