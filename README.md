# AT - Enviar guias de transporte

> Codigo PHP para enviar guias de transporte para a AT


Baseado no codigo do [StadaExp](https://www.portugal-a-programar.pt/forums/topic/57734-utilizar-webservices-da-at/?do=findComment&comment=609688) (sem dependencias externas, mas requere SOAP) e no código que disponibilizei em 2013 para a versão 5 do PHP.

## Requer

	- SoapClient activo: https://www.php.net/manual/en/class.soapclient.php
	- ChavePublicaAT.cer : (pasta certs) https://faturas.portaldasfinancas.gov.pt/factemipf_static/java/certificados.zip
	- TesteWebservices.pfx (pasta certs - convertido para pem, este ficheiro está disponivel no mesmo arquivo comprimido que a ChavePublica)
	- documentosTransporte.wsdl (pasta wsdl):  http://info.portaldasfinancas.gov.pt/pt/apoio_contribuinte/Documents/documentosTransporte.wsdl


## Converter TesteWebservices.pfx para TesteWebservices.pem: 

> openssl pkcs12 -in TesteWebservices.pfx -out TesteWebservices.pem -nodes

