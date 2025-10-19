<?php

use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;

require __DIR__.'/vendor/autoload.php';

$see = require __DIR__.'/config.php';

$company = new Company();
$company->setRuc('20123456789')
    ->setRazonSocial('GREEN SAC')
    ->setNombreComercial('GREEN')
    ->setAddress((new Address())
        ->setUbigueo('150101')
        ->setDepartamento('LIMA')
        ->setProvincia('LIMA')
        ->setDistrito('LIMA')
        ->setUrbanizacion('-')
        ->setDireccion('Av. Villa Nueva 221'));

// Detalle 1: Factura a dar de baja
$detail1 = new VoidedDetail();
$detail1->setTipoDoc('01') // Factura
    ->setSerie('F001')
    ->setCorrelativo('1')
    ->setDesMotivoBaja('ERROR EN CÁLCULOS');

// Detalle 2: Nota de Crédito de Factura a dar de baja
$detail2 = new VoidedDetail();
$detail2->setTipoDoc('07') // Nota de Crédito
    ->setSerie('FC01')
    ->setCorrelativo('1')
    ->setDesMotivoBaja('ERROR DE RUC');

$cDeBaja = new Voided();
$cDeBaja->setCorrelativo('001') // Correlativo del día
    ->setFecGeneracion(new \DateTime()) // Fecha de emisión de los comprobantes a dar de baja
    ->setFecComunicacion(new \DateTime()) // Fecha de envio de la C. de baja
    ->setCompany($company)
    ->setDetails([$detail1, $detail2]);

$result = $see->send($cDeBaja);

// Guardar XML
file_put_contents($cDeBaja->getName().'.xml',
                  $see->getFactory()->getLastXml());

if (!$result->isSuccess()) {
    echo 'Codigo Error: '.$result->getError()->getCode().PHP_EOL;
    echo 'Mensaje Error: '.$result->getError()->getMessage().PHP_EOL;
    exit();
}

$ticket = $result->getTicket();
echo 'Ticket : '.$ticket.PHP_EOL;
echo 'Consultando estado de la comunicación de baja...'.PHP_EOL;

// Esperar un momento antes de consultar
sleep(2);

$statusResult = $see->getStatus($ticket);

if (!$statusResult->isSuccess()) {
    echo 'Error al consultar estado:'.PHP_EOL;
    var_dump($statusResult->getError());
    exit();
}

$cdr = $statusResult->getCdrResponse();
echo 'Estado: '.$cdr->getDescription().PHP_EOL;

// Guardar CDR
file_put_contents('R-'.$cDeBaja->getName().'.zip', $statusResult->getCdrZip());

echo 'Comunicación de Baja procesada correctamente'.PHP_EOL;
