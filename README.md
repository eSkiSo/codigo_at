# AT - Enviar guias de transporte

> Codigo PHP para enviar guias de transporte para a AT


Baseado no codigo do [https://www.portugal-a-programar.pt/forums/topic/57734-utilizar-webservices-da-at/?do=findComment&comment=609688](StadaExp) (sem dependencias externas, mas requere SOAP) e no código que disponibilizei em 2013 para a versão 5 do PHP.

## Requer

	- [https://www.php.net/manual/en/class.soapclient.php](SoapClient) activo
	- [https://faturas.portaldasfinancas.gov.pt/factemipf_static/java/certificados.zip](ChavePublicaAT.cer) : (pasta certs) 
	- TesteWebservices.pfx (pasta certs - convertido para pem, este ficheiro está disponivel no mesmo arquivo comprimido que a ChavePublica)
	- [http://info.portaldasfinancas.gov.pt/pt/apoio_contribuinte/Documents/documentosTransporte.wsdl](documentosTransporte.wsdl) (pasta wsdl):  


## Converter TesteWebservices.pfx para TesteWebservices.pem: 

> openssl pkcs12 -in TesteWebservices.pfx -out TesteWebservices.pem -nodes

