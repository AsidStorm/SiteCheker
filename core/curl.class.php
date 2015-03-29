<?

namespace Core;

class CURL{
    private $strUrl;
    private $intCode;

    private $strHTML;

    private $arTrace;

    private $arCodes = array(
        'REDIRECT' => array(
            301, 302, 307
        )
    );

    private $arOptions = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    );

    public function __construct($strUrl, $boolBlockTransfer = false){
        $this->strUrl = $strUrl;

		if($boolBlockTransfer)
			$this->BlockTransfer();

        $this->Request();
    }
	
	private function BlockTransfer(){
		$this->arOptions[CURLOPT_RETURNTRANSFER] = false;
		$this->arOptions[CURLOPT_NOBODY]         = true;
	}

    private function Request(){
        $ch      = curl_init($this->strUrl);

        curl_setopt_array($ch, $this->arOptions);

        $content = curl_exec($ch);
        $err     = curl_errno($ch);
        $errmsg  = curl_error($ch);
        $header  = curl_getinfo($ch);

        curl_close($ch);

        if( in_array( (int) $header['http_code'], $this->arCodes['REDIRECT']) && $header['redirect_url'] !== ''){
            $this->arTrace[] = array(
                'URL'  => $this->strUrl,
                'CODE' => (int) $header['http_code']
            );

            $this->arOptions[CURLOPT_REFERER] = $this->strUrl;

            $this->strUrl    = $header['redirect_url'];

            $this->Request();
        }
        else{
            if(count($this->arTrace) >= 1)
                $this->arTrace[] = array(
                    'URL'  => $this->strUrl,
                    'CODE' => $header['http_code']
                );

            if( (int) $header['http_code'] === 200 ){
                $this->strHTML = $content;
                $this->intCode = (int) $header['http_code'];
            }
            else
                $this->intCode = (int) $header['http_code'];
        }
    }

    public function GetCode(){
        return $this->intCode;
    }
    public function GetHTML(){
        return $this->strHTML;
    }
    public function GetTrace(){
        return $this->arTrace;
    }
}