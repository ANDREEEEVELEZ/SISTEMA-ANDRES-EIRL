<?php

use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;

require __DIR__ . '/vendor/autoload.php';

$see = require __DIR__ . '/config.php';

// Cliente
$client = (new Client())
    ->setTipoDoc('6')
    ->setNumDoc('20000000001')
    ->setRznSocial('EMPRESA X');

// Emisor
$address = (new Address())
    ->setUbigueo('200601')
    ->setDepartamento('PIURA')
    ->setProvincia('SULLANA')
    ->setDistrito('SULLANA')
    ->setUrbanizacion('A.H. SANCHEZ CERRO')
    ->setDireccion('AV. JOSE DE LAMA NRO. 1192')
    ->setCodLocal('0000');

$company = (new Company())
    ->setRuc('10036736475')
    ->setRazonSocial('SOBRINO REQUENA DE SIANCAS LEONOR')
    ->setNombreComercial('SNACKS MR CHIPS')
    ->setAddress($address);

// Nota de Crédito
$note = (new Note())
    ->setUblVersion('2.1')
    ->setTipoDoc('07') // 07 = Nota de Crédito, 08 = Nota de Débito
    ->setSerie('FC03')
    ->setCorrelativo('1')
    ->setFechaEmision(new DateTime())
    ->setTipDocAfectado('01') // Factura afectada
    ->setNumDocfectado('F003-1') // Factura a la que se refiere (usa API Greenter: numDocfectado)
    ->setCodMotivo('01') // Anulación de la operación
    ->setDesMotivo('ERROR EN PRECIO')
    ->setTipoMoneda('PEN')
    ->setCompany($company)
    ->setClient($client)
    ->setMtoOperGravadas(100.00)
    ->setMtoIGV(18.00)
    ->setTotalImpuestos(18.00)
    ->setMtoImpVenta(118.00);

$item = (new SaleDetail())
    ->setCodProducto('P001')
    ->setUnidad('NIU')
    ->setCantidad(2)
    ->setMtoValorUnitario(50.00)
    ->setDescripcion('PRODUCTO 1')
    ->setMtoBaseIgv(100)
    ->setPorcentajeIgv(18.00)
    ->setIgv(18.00)
    ->setTipAfeIgv('10')
    ->setTotalImpuestos(18.00)
    ->setMtoValorVenta(100.00)
    ->setMtoPrecioUnitario(59.00);

$legend = (new Legend())
    ->setCode('1000')
    ->setValue('SON CIENTO DIECIOCHO CON 00/100 SOLES');

$note->setDetails([$item])
     ->setLegends([$legend]);

// Envío a SUNAT
$result = $see->send($note);

// Guardar XML firmado
file_put_contents($note->getName().'.xml',
                  $see->getFactory()->getLastXml());

// Verificar conexión
if (!$result->isSuccess()) {
    echo 'Codigo Error: '.$result->getError()->getCode().PHP_EOL;
    echo 'Mensaje Error: '.$result->getError()->getMessage().PHP_EOL;
    exit();
}

// Guardar CDR
file_put_contents('R-'.$note->getName().'.zip', $result->getCdrZip());

$cdr = $result->getCdrResponse();
$code = (int)$cdr->getCode();

if ($code === 0) {
    echo 'ESTADO: ACEPTADA'.PHP_EOL;
    if (count($cdr->getNotes()) > 0) {
        echo 'OBSERVACIONES:'.PHP_EOL;
        var_dump($cdr->getNotes());
    }
} else if ($code >= 2000 && $code <= 3999) {
    echo 'ESTADO: RECHAZADA'.PHP_EOL;
} else {
    echo 'Excepción'.PHP_EOL;
}

echo $cdr->getDescription().PHP_EOL;
