<?php

namespace macfly\hpoo\components;

use yii\httpclient\Client;
use yii\base\Component;

class HpooComponent extends Component
{
  public $sslVerifyPeer = true;
  public $timeout       = 5;
  public $proxy         = null;

  public $baseUrl       = null;
	public $login					= null,
	public $password			= null;

  private $client       = null;

  public function __construct()
	{
    $this->client = new Client([
				'baseUrl'					=> $this->baseUrl,
        'requestConfig'		=> ['format' => Client::FORMAT_JSON],
        'responseConfig'  => ['format' => Client::FORMAT_JSON],
      ]);
  }

	public function flowRunAsync($uuid, $inputs = array()) {

    $opts = array(
        'uuid'    => $uuid,
        );

    if(count($inputs) > 0) {
      $opts['inputs'] = $inputs;
    }

    $rp = $this->rq->('post', '/executions', $opts);

		print_r($rp);

	}

  public function flowStatus($runid) {

    $rp = $this->rq('get', sprintf("/executions?runId=%s", urlencode($runid)));

		print_r($rp);

#    return array_key_exists(0, $rp) ? $rp[0] : false;

  }

  public function rq($method, $url, $opts = array()) {

		$rq	= $this->client->createRequest()
			->setMethod($method)
	    ->setUrl($this->baseUrl . $url)
			->addHEaders(['Authorization' => 'Basic '.base64_encode(sprintf("%s:%s", $this->login, $this->password))]);

#    if(array_key_exists('header', $this->xcsrf)
#        && array_key_exists('header', $this->xcsrf)
#        && array_key_exists('header', $this->xcsrf)) {
#      $this->rq->setHeader('x-csrf-header', $this->xcsrf['header']);
#      $this->rq->setHeader('x-csrf-param', $this->xcsrf['param']);
#      $this->rq->setHeader('x-csrf-token', $this->xcsrf['token']);
#    }

#    $this->rq->setUrl(sprintf("%s/%s" , $this->url, $url));

    if(count($opts) > 0) {
      $rq->setData($opts);
    }

    $rp     = $rq->send();

		print_r($rp->headers);


#    $this->xcsrf['header']  = $rp->getHeader('x-csrf-header');
#    $this->xcsrf['param'] = $rp->getHeader('x-csrf-param');
#    $this->xcsrf['token'] = $rp->getHeader('x-csrf-token');
#
#    return json_decode($rp->getBody());

  }


}

