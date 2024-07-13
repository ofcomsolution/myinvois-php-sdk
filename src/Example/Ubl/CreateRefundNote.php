<?php

namespace Klsheng\Myinvois\Example\Ubl;

use Klsheng\Myinvois\Ubl\Invoice;
use Klsheng\Myinvois\Ubl\CreditNote;
use Klsheng\Myinvois\Ubl\DebitNote;
use Klsheng\Myinvois\Ubl\RefundNote;
use Klsheng\Myinvois\Ubl\SelfBilledInvoice;
use Klsheng\Myinvois\Ubl\SelfBilledCreditNote;
use Klsheng\Myinvois\Ubl\SelfBilledDebitNote;
use Klsheng\Myinvois\Ubl\SelfBilledRefundNote;
use Klsheng\Myinvois\Ubl\Address;
use Klsheng\Myinvois\Ubl\AddressLine;
use Klsheng\Myinvois\Ubl\Country;
use Klsheng\Myinvois\Ubl\LegalEntity;
use Klsheng\Myinvois\Ubl\Contact;
use Klsheng\Myinvois\Ubl\AccountingParty;
use Klsheng\Myinvois\Ubl\Party;
use Klsheng\Myinvois\Ubl\PartyIdentification;
//use Klsheng\Myinvois\Ubl\AllowanceCharge;
//use Klsheng\Myinvois\Ubl\Shipment;
//use Klsheng\Myinvois\Ubl\Delivery;
use Klsheng\Myinvois\Ubl\TaxTotal;
use Klsheng\Myinvois\Ubl\TaxScheme;
use Klsheng\Myinvois\Ubl\TaxCategory;
use Klsheng\Myinvois\Ubl\TaxSubTotal;
use Klsheng\Myinvois\Ubl\Item;
use Klsheng\Myinvois\Ubl\CommodityClassification;
use Klsheng\Myinvois\Ubl\Price;
use Klsheng\Myinvois\Ubl\ItemPriceExtension;
use Klsheng\Myinvois\Ubl\InvoiceLine;
//use Klsheng\Myinvois\Ubl\CreditNoteLine;
//use Klsheng\Myinvois\Ubl\DebitNoteLine;
//use Klsheng\Myinvois\Ubl\AdditionalDocumentReference;
use Klsheng\Myinvois\Ubl\LegalMonetaryTotal;
use Klsheng\Myinvois\Ubl\InvoicePeriod;
//use Klsheng\Myinvois\Ubl\PayeeFinancialAccount;
//use Klsheng\Myinvois\Ubl\PaymentMeans;
//use Klsheng\Myinvois\Ubl\PaymentTerms;
//use Klsheng\Myinvois\Ubl\BillingReference;
//use Klsheng\Myinvois\Ubl\PrepaidPayment;
//use Klsheng\Myinvois\Ubl\TaxExchangeRate;
//use Klsheng\Myinvois\Ubl\InvoiceDocumentReference;
//use Klsheng\Myinvois\Ubl\Extension\UBLExtensions;
//use Klsheng\Myinvois\Ubl\Extension\UBLExtensionItem;
//use Klsheng\Myinvois\Ubl\Extension\UBLDocumentSignatures;
//use Klsheng\Myinvois\Ubl\Extension\SignatureInformation;
//use Klsheng\Myinvois\Ubl\Extension\Signature;
//use Klsheng\Myinvois\Ubl\Extension\SignInfo;
//use Klsheng\Myinvois\Ubl\Extension\SignInfoReference;
//use Klsheng\Myinvois\Ubl\Extension\SignInfoTransform;
//use Klsheng\Myinvois\Ubl\Extension\KeyInfo;
//use Klsheng\Myinvois\Ubl\Extension\KeyInfoX509Data;
//use Klsheng\Myinvois\Ubl\Extension\SignatureObject;
//use Klsheng\Myinvois\Ubl\Extension\QualifyingProperties;
//use Klsheng\Myinvois\Ubl\Extension\SignedProperties;
//use Klsheng\Myinvois\Ubl\Extension\SignedSignatureProperties;
use Klsheng\Myinvois\Ubl\Builder\XmlDocumentBuilder;
use Klsheng\Myinvois\Ubl\Builder\JsonDocumentBuilder;
use Klsheng\Myinvois\Ubl\Constant\MSICCodes;
use Klsheng\Myinvois\Ubl\Constant\InvoiceTypeCodes;

class CreateRefundNote {

    // The data that we got from the client.store it in here so we don't keep passing it around as parameters.
    private $DocumentID;
    private $PostData;

    // We can't do what we did with the class members above with DocumentObject,
    // since we don't know what the API/SDK will possibly do in the future with our $document object that's being passed around.
    // (they might do some manipulation internally,and return a different object,making our references invalid)
    //    private $DocumentObject;

    private function preDocumentCreate ( $DocumentID, $PostData ) {
        $this->DocumentID      = $DocumentID;
        $this->PostData        = $PostData;
    }

    public function createXmlDocument ($DocumentID, $PostData ) {
        $this->preDocumentCreate( $DocumentID, $PostData );
        $document = $this->createDocument();

        return ( new XmlDocumentBuilder() )->getDocument( $document );
    }

    public function createJsonDocument ( $DocumentID, $PostData ) {
        $this->preDocumentCreate( $DocumentID, $PostData );
        $document = $this->createDocument();

        return ( new JsonDocumentBuilder() )->getDocument( $document );
    }


    private function createDocument () {
        $document = new Invoice();

        $document->setId( $this->DocumentID );
        $document = $this->setIssueDateTime($document);
        $document = $this->setInvoicePeriod($document);
        $document = $this->setSupplier($document);
        $document = $this->setCustomer($document);
        $document = $this->setDocumentLine($document);
        $document = $this->setLegalMonetaryTotal($document);
        $document = $this->setTaxTotal($document);

        return $document;
    }

    private function setIssueDateTime($document)
    {
        return $document->setIssueDateTime(new \DateTime($this->PostData['IssuedDateTime']));
    }


    private function setSupplier($document)
    {
        $DocumentData = $this->PostData;
        $AccountingSupplierParty = $DocumentData['AccountingSupplierParty'];

        $address = new Address();
        $address->setCityName($AccountingSupplierParty['CityName']);
        $address->setPostalZone($AccountingSupplierParty['PostalZone']);
        $address->setCountrySubentityCode($AccountingSupplierParty['CountrySubentity']);

        // We're extracting it here manually from the full address text using explode
        // We'll make the assumption that they'll use a comma as a seperator for now.
        $ExtractedAddressLine = explode($AccountingSupplierParty['StreetName'],',');

        foreach($ExtractedAddressLine as $Line)
        {
            $addressLine = new AddressLine();
            $addressLine->setLine($Line);
            $address->addAddressLine($addressLine);
        }

        $country = new Country();
        $country->setIdentificationCode($AccountingSupplierParty['Country']);
        $address->setCountry($country);

        $legalEntity = new LegalEntity();
        $legalEntity->setRegistrationName($AccountingSupplierParty['RegistrationName']);

        $contact = new Contact();
        $contact->setName($AccountingSupplierParty['Contact']['Name']);
        $contact->setTelephone($AccountingSupplierParty['Contact']['Telephone']);
        $contact->setElectronicMail($AccountingSupplierParty['Contact']['ElectronicMail']);

        $supplier = new Party();

        $partyIdentification = new PartyIdentification();
        $partyIdentification->setId($AccountingSupplierParty['CompanyID'], 'TIN');
        $supplier->addPartyIdentification($partyIdentification);

        $partyIdentification = new PartyIdentification();
        $partyIdentification->setId($AccountingSupplierParty['PartyIdentification'], 'BRN');
        $supplier->addPartyIdentification($partyIdentification);

        $supplier->setPostalAddress($address);
        $supplier->setLegalEntity($legalEntity);
        $supplier->setContact($contact);

        $msicCode = $AccountingSupplierParty['IndustryClassificationCode'];
        $msicCodeDesc = MSICCodes::getDescription($msicCode);
        $supplier->setIndustryClassificationCode($msicCode, $msicCodeDesc);

        $accountingParty = new AccountingParty();

        $accountingParty->setParty($supplier);

        return $document->setAccountingSupplierParty($accountingParty);
    }

    private function setCustomer($document)
    {
        $DocumentData = $this->PostData;
        $AccountingCustomerParty = $DocumentData['AccountingCustomerParty'];

        $address = new Address();
        $address->setCityName($AccountingCustomerParty['CityName']);
        $address->setPostalZone($AccountingCustomerParty['PostalZone']);
        $address->setCountrySubentityCode($AccountingCustomerParty['CountrySubentity']);

        // We're extracting it here manually from the full address text using explode
        // We'll make the assumption that they'll use a comma as a seperator for now.
        $ExtractedAddressLine = explode($AccountingCustomerParty['StreetName'],',');

        foreach($ExtractedAddressLine as $Line)
        {
            $addressLine = new AddressLine();
            $addressLine->setLine($Line);
            $address->addAddressLine($addressLine);
        }

        // TODO: Hardcoding this since it has a type mismatch between Peppol and LHDN,map them later on
        $country = new Country();
        $country->setIdentificationCode($AccountingCustomerParty['Country']);
        $address->setCountry($country);

        $legalEntity = new LegalEntity();
        $legalEntity->setRegistrationName($AccountingCustomerParty['RegistrationName']);

        $contact = new Contact();
        $contact->setName($AccountingCustomerParty['Contact']['Name']);
        $contact->setTelephone($AccountingCustomerParty['Contact']['Telephone']);
        $contact->setElectronicMail($AccountingCustomerParty['Contact']['ElectronicMail']);

        $customer = new Party();

        $partyIdentification = new PartyIdentification();
        $partyIdentification->setId($AccountingCustomerParty['CompanyID'], 'TIN');
        $customer->addPartyIdentification($partyIdentification);

        $partyIdentification = new PartyIdentification();
        $partyIdentification->setId($AccountingCustomerParty['PartyIdentification'], 'BRN');
        $customer->addPartyIdentification($partyIdentification);

        $customer->setPostalAddress($address);
        $customer->setLegalEntity($legalEntity);
        $customer->setContact($contact);

        $msicCode = $AccountingCustomerParty['IndustryClassificationCode'];
        $msicCodeDesc = MSICCodes::getDescription($msicCode);
        $customer->setIndustryClassificationCode($msicCode, $msicCodeDesc);

        $accountingParty = new AccountingParty();
        $accountingParty->setParty($customer);

        return $document->setAccountingCustomerParty($accountingParty);
    }


    private function setDocumentLine($document)
    {

        $DocumentData = $this->PostData;

        $documentLines = [];
        $taxScheme = new TaxScheme();
        $taxScheme->setId('OTH');

        foreach($DocumentData['InvoiceLine'] as $i => $Line)
        {

            $taxTotal = new TaxTotal();
            $taxTotal->setTaxAmount($Line['TaxAmount']);

            $taxCategory = new TaxCategory();
            $taxCategory->setId('01');
            $taxCategory->setPercent($Line['TaxRate']);
            // $taxCategory->setTaxExemptionReason('Exempt New Means of Transport');
            $taxCategory->setTaxScheme($taxScheme);

            $taxSubTotal = new TaxSubTotal();
            $taxSubTotal->setTaxableAmount($Line['LineExtensionAmount']);
            $taxSubTotal->setTaxAmount($Line['TaxAmount']);
            $taxSubTotal->setPercent($Line['TaxRate']);
            $taxSubTotal->setTaxCategory($taxCategory);
            $taxTotal->addTaxSubTotal($taxSubTotal);

            $country = new Country();
            $country->setIdentificationCode($Line['OriginCountry']);

            $item = new Item();
            $item->setDescription($Line['Name']);

            if(!empty($Line['ProductTariffCode']))
            {
                $commodityClassification = new CommodityClassification();
                $commodityClassification->setItemClassificationCode($Line['ProductTariffCode'], 'PTC');
                $item->addCommodityClassification($commodityClassification);
            }

            $commodityClassification = new CommodityClassification();
            $commodityClassification->setItemClassificationCode($Line['Classification'], 'CLASS');
            $item->addCommodityClassification($commodityClassification);

            $price = new Price();
            $price->setPriceAmount($Line['PriceAmount']);

            $itemPriceExtension = new ItemPriceExtension();
            $itemPriceExtension->setAmount($Line['LineExtensionAmount']);

            $documentLine = new InvoiceLine();
            $documentLine->setId($i + 1);
            $documentLine->setInvoicedQuantity($Line['InvoicedQuantity']);
            $documentLine->setLineExtensionAmount($Line['LineExtensionAmount']);
            $documentLine->setTaxTotal($taxTotal);
            $documentLine->setItem($item);
            $documentLine->setPrice($price);
            $documentLine->setItemPriceExtension($itemPriceExtension);
            $documentLines[] = $documentLine;
        }

        return $document->setInvoiceLines($documentLines);
    }

    private function setLegalMonetaryTotal($document)
    {

        $DocumentData = $this->PostData;

        $legalMonetaryTotal = new LegalMonetaryTotal();
        $legalMonetaryTotal->setLineExtensionAmount($DocumentData['LegalMonetaryTotal']['LineExtensionAmount']);
        $legalMonetaryTotal->setTaxExclusiveAmount($DocumentData['LegalMonetaryTotal']['TaxExclusiveAmount']);
        $legalMonetaryTotal->setTaxInclusiveAmount($DocumentData['LegalMonetaryTotal']['TaxInclusiveAmount']);
        // $legalMonetaryTotal->setAllowanceTotalAmount(1436.50);
        // $legalMonetaryTotal->setChargeTotalAmount(1436.50);
        // $legalMonetaryTotal->setPayableRoundingAmount(0.30);
        $legalMonetaryTotal->setPayableAmount($DocumentData['LegalMonetaryTotal']['PayableAmount']);

        return $document->setLegalMonetaryTotal($legalMonetaryTotal);
    }

    private function setInvoicePeriod($document)
    {
        $invoicePeriod = new InvoicePeriod();
        $DocumentData = $this->PostData;

        $StartDate = new \DateTime($DocumentData['InvoicePeriod']['StartDate']);
        $invoicePeriod->setStartDate($StartDate);

        $EndDate = new \DateTime($DocumentData['InvoicePeriod']['EndDate']);
        $invoicePeriod->setEndDate($EndDate);

        return $document->setInvoicePeriod($invoicePeriod);
    }
    private function setTaxTotal($document)
    {
        $DocumentData = $this->PostData;

        $taxTotal = new TaxTotal();
        $taxTotal->setTaxAmount($DocumentData['TaxTotal']['TaxAmount']);

        $taxScheme = new TaxScheme();
        $taxScheme->setId('OTH', 'UN/ECE 5153', '6');

        $taxCategory = new TaxCategory();
        $taxCategory->setId('01');
        $taxCategory->setTaxScheme($taxScheme);

        $taxSubTotal = new TaxSubTotal();
        $taxSubTotal->setTaxableAmount($DocumentData['TaxTotal']['TaxableAmount']);
        $taxSubTotal->setTaxAmount($DocumentData['TaxTotal']['TaxAmount']);
        $taxSubTotal->setTaxCategory($taxCategory);
        $taxTotal->addTaxSubTotal($taxSubTotal);

        return $document->setTaxTotal($taxTotal);
    }
}
