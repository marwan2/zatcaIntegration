## McLedger ZATCA Integration

This is to integrate McLedger Platform with ZATCA KSA Invoicing Specifications

## UBL package files changed

- /opt/lampp/htdocs/mcledger_zatca/vendor/num-num/ubl-invoice/src/Address.php
- /opt/lampp/htdocs/mcledger_zatca/vendor/num-num/ubl-invoice/src/Invoice.php
- /opt/lampp/htdocs/mcledger_zatca/vendor/num-num/ubl-invoice/src/AdditionalDocumentReference.php
- /opt/lampp/htdocs/mcledger_zatca/vendor/num-num/ubl-invoice/src/Attachment.php
- /opt/lampp/htdocs/mcledger_zatca/vendor/num-num/ubl-invoice/src/TaxTotal.php

## Data Notes
# Business (Supplier Party)
 	- Required fields:
 		country ISO2
	    Street name
	    Building no
	    City
	    Postal code
	    TRN
	    Legal Registration Name
	    Identification ID
	- VAT registration number start & end with number 3

	The seller identification must exist only once with one of the scheme ID (CRN, MOM, MLS, SAG, OTH) 
	and must contain only alphanumeric characters.
	- Commercial Registration number with "CRN" as schemeID
	- Momra license with "MOM" as schemeID
	- MLSD license with "MLS" as schemeID
	- Sagia license with "SAG" as schemeID
	- Other OD with "OTH" as schemeID

 # Customer (Customer Party)
 - Identification ID
 - Buyer Registration Name
 - Buyer Address - District field, minimum limit is 1 character and maximum limit is 127 characters.
 - Buyer Address Additional number (PlotIdentification) must be 4 digits if it exists.
 - If customer country SA, this fields required:
 	- street name
 	- additional number
 	- building number
 	- Neighborhood
 	- city
 	- postal code
 	- country code
