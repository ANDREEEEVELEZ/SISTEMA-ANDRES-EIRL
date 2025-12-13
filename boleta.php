<?php

use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;

require __DIR__.'/vendor/autoload.php';

$see = require __DIR__.'/config.php';

// Cliente
$client = (new Client())
    ->setTipoDoc('1') // DNI
    ->setNumDoc('12345678')
    ->setRznSocial('JUAN PEREZ');

// Emisor
$address = (new Address())
    ->setUbigueo('150101')
    ->setDepartamento('LIMA')
    ->setProvincia('LIMA')
    ->setDistrito('LIMA')
    ->setUrbanizacion('-')
    ->setDireccion('Av. Villa Nueva 221')
    ->setCodLocal('0000');

$company = (new Company())
    ->setRuc('20123456789')
    ->setRazonSocial('GREEN SAC')
    ->setNombreComercial('GREEN')
    ->setAddress($address);

// Boleta
$boleta = (new Invoice())
    ->setUblVersion('2.1')
    ->setTipoOperacion('0101')
    ->setTipoDoc('03') // Boleta - Catalog. 01
    ->setSerie('B003')
    ->setCorrelativo('1')
    ->setFechaEmision(new DateTime())
    ->setFormaPago(new FormaPagoContado())
    ->setTipoMoneda('PEN')
    ->setCompany($company)
    ->setClient($client)
    ->setMtoOperGravadas(100.00)
    ->setMtoIGV(18.00)
    ->setTotalImpuestos(18.00)
    ->setValorVenta(100.00)
    ->setSubTotal(118.00)
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

$boleta->setDetails([$item])
       ->setLegends([$legend]);

// Generar solo XML firmado (NO se envía a SUNAT aún)
$xml = $see->getXmlSigned($boleta);

file_put_contents($boleta->getName().'.xml', $xml);

echo 'XML de Boleta generado: '.$boleta->getName().'.xml'.PHP_EOL;
echo 'Esta boleta debe enviarse en un Resumen Diario'.PHP_EOL;
