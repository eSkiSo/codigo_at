<?php

/**
 * Codigo PHP para enviar guias de transporte para a AT
 *
 * Baseado no codigo do StadaExp (sem dependencias externas, mas requer SOAP) e no código que disponibilizei em 2013 para a versão 5 do PHP.
 *
 *
 * Codigo StadaExp: https://www.portugal-a-programar.pt/forums/topic/57734-utilizar-webservices-da-at/?do=findComment&comment=609688
 *
 *
 * Requer:
 *     - SoapClient activo: https://www.php.net/manual/en/class.soapclient.php
 *     - ChavePublicaAT.cer : (pasta certs) https://faturas.portaldasfinancas.gov.pt/factemipf_static/java/certificados.zip
 *     - TesteWebservices.pfx (pasta certs - convertido para pem, este ficheiro está disponivel no mesmo arquivo comprimido que a ChavePublica)
 *     - documentosTransporte.wsdl (pasta wsdl):  http://info.portaldasfinancas.gov.pt/pt/apoio_contribuinte/Documents/documentosTransporte.wsdl
 *
 *      Converter TesteWebservices.pfx para TesteWebservices.pem (Password: TESTEwebservice): 
 *              openssl pkcs12 -in TesteWebservices.pfx -out TesteWebservices.pem -nodes
 */

// Dados de exemplo
$transport = new stdClass();
$transport->TaxRegistrationNumber = '599999993';
$transport->CompanyName = 'Nome Empresa Lda';
$transport->CompanyAddress = new stdClass();
$transport->CompanyAddress->Addressdetail = 'Rua da Empresa, 123';
$transport->CompanyAddress->City = 'Maia';
$transport->CompanyAddress->PostalCode = '4470-263';
$transport->CompanyAddress->Country = 'PT';
$transport->DocumentNumber = bin2hex(openssl_random_pseudo_bytes(8));
$transport->MovementStatus = 'N';
$transport->MovementDate = date('Y-m-d');
$transport->MovementType = 'GT';

$transport->CustomerName = 'Nome Cliente Lda';
$transport->CustomerTaxID = 599999993;
$transport->CustomerAddress = new stdClass();
$transport->CustomerAddress->Addressdetail = 'Local';
$transport->CustomerAddress->City = 'Lisboa';
$transport->CustomerAddress->PostalCode = '2775-089';
$transport->CustomerAddress->Country = 'PT';

$transport->AddressFrom = new stdClass();
$transport->AddressFrom->Addressdetail = 'Local carga';
$transport->AddressFrom->City = 'Maia';
$transport->AddressFrom->PostalCode = '4470-263';
$transport->AddressFrom->Country = 'PT';

$transport->AddressTo = new stdClass();
$transport->AddressTo->Addressdetail = 'Local entrega';
$transport->AddressTo->City = 'Lisboa';
$transport->AddressTo->PostalCode = '2775-089';
$transport->AddressTo->Country = 'PT';

$transport->VehicleID = '00-00-OK';

$transport->MovementStartTime = date('Y-m-d\TH:i:s', mktime(date('H') + 1, date('i'), 0, date('m'), date('d'), date('Y')));
$transport->MovementEndTime = date('Y-m-d\TH:i:s', mktime(date('H') + 5, date('i'), 0, date('m'), date('d'), date('Y')));

$transport->Line = [];
$transport->Line[] = (function () {
    $line = new stdClass();

    $line->ProductDescription = 'prod1';
    $line->Quantity = 1;
    $line->UnitOfMeasure = 'Kg';
    $line->UnitPrice = 11;

    return $line;
})();

$transport->Line[] = (function () {
    $line = new stdClass();

    $line->ProductDescription = 'prod2';
    $line->Quantity = 1;
    $line->UnitOfMeasure = 'Kg';
    $line->UnitPrice = 12;

    return $line;
})();

//Ficheiro WSDL que usamos como template para gerar l XML necessario
$wsdl = 'wsdl/documentosTransporte.wsdl';

//Para ambiente real é necessário alterar estes campos para os valores reais, URL deixa de ser 701 e passa para 401
$options = [
    'local_cert' => 'certs/TesteWebservices.pem',
    'encoding' => 'utf-8',
    'soap_version' => SOAP_1_2,
    'at_username' => '599999993/37',
    'at_password' => 'testes1234',
    'at_public_key' => 'certs/ChavePublicaAT.cer',
    'location' => 'https://servicos.portaldasfinancas.gov.pt:701/sgdtws/documentosTransporte',
    'uri' => 'https://servicos.portaldasfinancas.gov.pt:701/sgdtws/documentosTransporte',
];

try {
    $client = new SoapClientAT($wsdl, $options);
    echo "<font color=green>SUCESSO</font><pre>";
    print_r($client->envioDocumentoTransporte($transport));
    echo "</pre>";
}
catch (Exception $e) {
    echo "<font color=red>ERRO</font><pre>";
    print_r($e);
    echo "</pre>";
    die();
}


/**
 * Class SoapClientAT
 */
class SoapClientAT extends SoapClient {
    /**
     * @var array|null
     */
    private $options = null;

    /**
     * SoapClientAT constructor.
     * @param string $wsdl
     * @param array $options
     */
    public function __construct($wsdl, $options) {
        $this->options = $options;
        parent::__construct($wsdl, $options);
    }

    /**
     * Encriptar password e created com chave unica gerada
     * @param  string $data password ou created date
     * @param  string $key  chave unica gerada
     * @return string       texto encriptado com chave gerada
     */
    function encrypt($data, $key) {
        return  openssl_encrypt($data, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
    }

    /**
     * Retorna NONCE para cabecalho de autenticacao
     * @param  string $key         chave unica gerada
     * @param  string $certificado caminho para o ficheiro .cer
     * @return string              nonce
     */
    function generateNonce($key, $certificado) {
        openssl_public_encrypt ($key, $crypttext, openssl_pkey_get_public ( file_get_contents ( $certificado ) ), OPENSSL_PKCS1_PADDING );
        return $crypttext;
    }

    /**
     * @param DOMDocument $document
     * @return DOMElement
     */
    private function createHeaderNode($document){
        $publicKey = $this->options['at_public_key'];

        // Gerar chave unica
        $key = substr ( md5 ( uniqid ( microtime () ) ), 0, 16 );

        $password = $this->encrypt($this->options['at_password'], $key);
        $nonce = $this->generateNonce ($key, $publicKey);
        $created = $this->encrypt(gmdate ( 'Y-m-d\TH:i:s\.00\Z' ), $key);

        $usernameToken = $document->createElement('wss:UsernameToken');
        $usernameTokenData = [
            'wss:Username' => $this->options['at_username'],
            'wss:Password' => base64_encode($password),
            'wss:Nonce' => base64_encode($nonce),
            'wss:Created' => base64_encode($created),
        ];

        foreach ($usernameTokenData as $tag => $value) {
            $usernameToken->appendChild($document->createElement($tag, $value));
        }

        $security = $document->createElement('wss:Security');
        $security->appendChild($usernameToken);
        $security->setAttributeNS('http://www.w3.org/2000/xmlns/',
            'xmlns:wss',
            'http://schemas.xmlsoap.org/ws/2002/12/secext'
        );

        $header = $document->createElement('env:Header');
        $header->appendChild($security);

        return $header;
    }

    /**
     * @param $request
     * @return string
     */
    private function embedHeader($request) {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = true;
        $document->loadXML($request);

        $body = $document->getElementsByTagName('Body')->item(0);
        $header = $this->createHeaderNode($document);

        $envelope = $document->getElementsByTagName('Envelope');
        $envelope->item(0)->insertBefore($header, $body);

        // Must change the namespace of the Envelope tag to match the one specified in the WSDL otherwise we'll get
        // an HTTP 500 Error with the text "Internal Error"
        $envelope->item(0)->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:env',
            'http://schemas.xmlsoap.org/soap/envelope/'
        );

        return $document->saveXML();
    }

    /**
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param int $one_way
     *
     * @return string
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0) {
        $request = $this->embedHeader($request);
        return parent::__doRequest($request, $location, $action, $version, $one_way);
    }
}
