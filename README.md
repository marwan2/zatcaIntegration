## McLedger ZATCA Integration

This is to integrate McLedger Platform with ZATCA KSA Invoicing Specifications.

## Data Notes
# Business (Supplier Party)
 	# Required fields:
        - Country ISO2
        - Street name
        - Building no (4 digits)
        - City name
        - Postal code
        - Plot Identification (4 letters, additional no.)
        - District
        - Country Subentity
	    - TRN no. (15 chars, Start and end with number 3)
	    - Legal Registration Name
	    - Identification scheme (Check details below)
	    	- Identification Code
	    	- Identification Data (based on code)
	    	* default now ('OTH') with value 'BusinessID'
	    - Tax Schema (default 'VAT')

	* Identification scheme
	The seller identification must exist only once with one of the scheme ID (CRN, MOM, MLS, SAG, OTH) 
	and must contain only alphanumeric characters.
	- CRN: Commercial Registration number
	- MOM: Momra license
	- MLS: MLSD license
	- SAG: Sagia license
	- OTH: Other OD

 # Customer (Customer Party)
	 - Identification ID
	 - Buyer Registration Name (TRN)
	 - Buyer Address - District field, minimum limit is 1 character and maximum limit is 127 characters.
	 - Buyer Address Additional number (PlotIdentification) must be 4 digits if it exists.
	 - If customer country SA, this fields required:
	 	- Legal name
	 	- Street name
	 	- Plot Identification: (Additional number)
	 	- Additional street name
	 	- Building number
	 	- Neighborhood
	 	- City name
	 	- Postal code
	 	- District
	 	- Country ISO2 Code
	 	- Already we have (Email, Phone, Fax, Customer name)
	 	- Tax Schema (default 'VAT')
	 	- Identification scheme
	 	  default now ('OTH') with value 'CustomerID'

# Invoice Lines
	- Get tax_included per single invoice
	- Item unit as Zatca specification
		- Currently we use value 'PCE'
	- Fix: InvoicePeriod value
	- Tax Exemption Reason Code (from Zatca docs)
	- Tax Exemption Reason Text (from Zatca docs)
	- Classified Tax Category ID: (find proper solution to specify lineItem with ZeroRated, Exempt or Standard rated) 

# Invoice
	- Review: Allowance Amount in Zatca docs, to be applied
	- Payment means code (as per payment terms)
	- QR code (Refer to Zatca docs)
	- PIH: previous invoice hash (Refer to Zatca docs)
	- CSID
	- UUID

# Validations
	- Street name: max chars limit (127 characters)
	- Builiding number: max chars limit (127 characters)
	- Additional number: max chars limit (127 characters)
	- Plot Identification (Additional number): 4 digits only
	- District, Additional Number, Additional StreetName is required if Customer ISO code is "SA"
	- TaxCategoryID should have value:
		- S: standard rated
		- E: Exempt 
		- Z: Zero rated
	- Classified Tax Category ID: should have value:
		- S: standard rated
		- E: Exempt 
		- Z: Zero rated

ZeroRated items: a record added to ERP table "trans_tax_details"