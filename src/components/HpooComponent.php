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
	public $login					= null;
	public $password			= null;

  private $client       = null;
	private $xcsrf				= null;

  public function __construct()
	{
		$this->resetXcsrf();
    $this->client = new Client([
        'requestConfig'		=> ['format' => Client::FORMAT_JSON],
        'responseConfig'  => ['format' => Client::FORMAT_JSON],
      ]);
  }

  public function resetXcsrf() {
    $this->xcsrf  = [];
  }

	public function flowRunAsync($uuid, $inputs = array())
	{
    $opts	= ['flowUuid'	=> $uuid];

    if(count($inputs) > 0)
		{
      $opts['inputs'] = $inputs;
    }

    $runid	= $this->rq('post', '/executions', $opts);

		return $runid;
	}

	public function flowRunSync($uuid, $inputs = array())
	{
		$runid			= $this->flowRunAsync($uuid, $inputs);

		while(true)
		{
			$rp		= $this->flowStatus($runid);

			if(!(array_key_exists('status', $rp) && $rp['status'] == 'RUNNING'))
			{
				break;
			}

			sleep(1);
		}

		return $rp;	

	}

  public function flowStatus($runid)
	{
    $rp	= $this->rq('get', sprintf("/executions/%s/summary", urlencode($runid)));
    return array_key_exists(0, $rp) ? $rp[0] : false;
  }

	public function flowLog($runid)
	{
    $rp	= $this->rq('get', sprintf("/executions/%s/execution-log", urlencode($runid)));
    return $rp;
	}

  public function rq($method, $url, $opts = array())
	{
		$rq	= $this->client->createRequest()
			->setMethod($method)
			->setHeaders(['content-type' => 'application/json'])
	    ->setUrl($this->baseUrl . $url)
			->addHEaders(['Authorization' => 'Basic '.base64_encode(sprintf("%s:%s", $this->login, $this->password))]);

		foreach(['header', 'param', 'token'] as $key)
		{
	    if(array_key_exists($key, $this->xcsrf))
			{
				$rq->headers->set(sprintf("x-csrf-%s", $key), $this->xcsrf[$key]);
			}
		}

    if(count($opts) > 0) {
      $rq->setData($opts);
    }

    $rp     = $rq->send();

		if($rp->isOk)
		{
			foreach(['header', 'param', 'token'] as $key)
			{
				if(!is_null($value = $rp->headers->get(sprintf("x-csrf-%s", $key)))) {
					$this->xcsrf[$key]	= $value;
				}
			}

			return $rp->data;
		} else {
			throw new \Exception(sprintf("Return Code: %s, Return: %s", $rp->getStatusCode(), $rp->content));
		}
  }

}

