# Clearpay Magento 2 Extension Changelog

## Version 3.1.0

_Wed 18 Dec 2019 (AEDT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.0.2 and later
- Magento Enterprise Edition (EE) version 2.0.2 and later

### Highlights

- API upgrade from v1 to v2, including the introduction of "Deferred Payment Flow".
- Improvements to quote validation prior to payment capture.
- Improvements to the "Exclude Category" feature.

---

## Version 3.0.6

_Wed 25 Sep 2019 (GMT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.0.2 and later
- Magento Enterprise Edition (EE) version 2.0.2 and later

### Highlights

- Added a new feature to allow Clearpay to be disabled for a specified set of product categories.
- Improved compatibility between Afterpay and Clearpay modules in multi-regional Magento installations.
- Improved support for Credit Memos used in conjunction with Clearpay orders in Magento Enterprise installations.
- Improved support for Product Detail Pages (PDP) where the main price element is missing the "data-price-type" attribute.
- Upgraded assets for modal popups.
- Removed potentially sensitive information from log files.

---

## Version 3.0.5

_04 Sep 2019 (GMT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.0.2 and later
- Magento Enterprise Edition (EE) version 2.0.2 and later

### Highlights

- Added a new feature to notify admin if an exception occurs while finalising the Magento order.
- Improved handling of SQL errors during the order finalisation process.
- Improved handling of variant selection, where the default variant was ineligible for purchase with Clearpay.
- Improved handling of virtual product orders.
- Improved handling of orders from logged-in customers where no billing address was selected.
- Improved formatting and cross-browser compatibility for PDP elements and extended support for uncommon product types.
- Improved checkout field validation.
- Improved logging during cron tasks.
- Extended debug logging.

---

## Version 3.0.4

_25 Jan 2019 (GMT)_
 
### Supported Editions & Versions

- Magento Community Edition (CE) version 2.0.2 and later
- Magento Enterprise Edition (EE) version 2.0.2 and later

### Highlights

- Improved support for HTTP/2.
