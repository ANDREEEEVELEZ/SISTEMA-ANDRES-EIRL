<?php

use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\Document;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Summary\SummaryDetail;

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

// Detalle 1: Nota de Crédito de Boleta
$detail = new SummaryDetail();
$detail->setTipoDoc('07') // Nota de Credito
    ->setSerieNro('BC01-1')
    ->setDocReferencia((new Document()) // Documento relacionado (Boleta)
        ->setTipoDoc('03')
        ->setNroDoc('B001-1'))
    ->setEstado('1') // Emisión
    ->setClienteTipo('1') // Tipo documento identidad: DNI
    ->setClienteNro('12345678') // Nro de documento identidad
    ->setTotal(118)
    ->setMtoOperGravadas(100)
    ->setMtoIGV(18);

// Detalle 2: Boleta emitida
$detail2 = new SummaryDetail();
$detail2->setTipoDoc('03') // Boleta
    ->setSerieNro('B001-2')
    ->setEstado('1') // Emisión
    ->setClienteTipo('1')
    ->setClienteNro('87654321')
    ->setTotal(236)
    ->setMtoOperGravadas(200)
    ->setMtoIGV(36);

// Detalle 3: Boleta anulada
$detail3 = new SummaryDetail();
$detail3->setTipoDoc('03') // Boleta
    ->setSerieNro('B001-3')
    ->setEstado('3') // Anulación
    ->setClienteTipo('1')
    ->setClienteNro('11223344')
    ->setTotal(59)
    ->setMtoOperGravadas(50)
    ->setMtoIGV(9);

$resumen = new Summary();
$resumen->setFecGeneracion(new \DateTime()) // Fecha de emisión de las boletas
    ->setFecResumen(new \DateTime()) // Fecha de envío del resumen diario
    ->setCorrelativo('001') // Correlativo del día
    ->setCompany($company)
    ->setDetails([$detail, $detail2, $detail3]);

$result = $see->send($resumen);

// Guardar XML
file_put_contents($resumen->getName().'.xml',
                  $see->getFactory()->getLastXml());

if (!$result->isSuccess()) {
    echo 'Codigo Error: '.$result->getError()->getCode().PHP_EOL;
    echo 'Mensaje Error: '.$result->getError()->getMessage().PHP_EOL;
    exit();
}

$ticket = $result->getTicket();
echo 'Ticket : '.$ticket.PHP_EOL;
echo 'Consultando estado del resumen...'.PHP_EOL;

// Esperar un momento antes de consultar (en producción debes esperar más tiempo)
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
file_put_contents('R-'.$resumen->getName().'.zip', $statusResult->getCdrZip());

echo 'Resumen Diario procesado correctamente'.PHP_EOL;
