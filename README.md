# Clearpay for Magento 2

## Installation

### Install using Composer (Recommended)
<ol>
<li> From the CLI, run the following commands to install the Clearpay module.
<br/>The right installation command is dependent on your Magento 2 version:

| Magento version | Command to run                                              |
|-----------------|-------------------------------------------------------------|
| 2.4.*           | composer require clearpay/module-clearpay:^5         |
| 2.3.*           | composer require clearpay/module-clearpay:^4         |
| < 2.3.0         | composer require clearpay/module-clearpay:^3         |
</li>
<li> Run Magento install/upgrade scripts: <code><em>php bin/magento setup:upgrade</em></code> </li>
<li> Compile dependency injection: <code><em>php bin/magento setup:di:compile</em></code> </li>
<li> Deploy static view files (production mode only): <code><em>php bin/magento setup:static-content:deploy</em></code> </li>
<li> Flush Magento cache: <code><em>php bin/magento cache:flush</em></code></li>
</ol>

### Install manually
<ol>
	<li> Download the Clearpay module for Magento 2 - Available as a .zip or tar.gz file from the Clearpay GitHub directory.
   <br/>The right installation module is dependent on your Magento 2 version:

| Magento version | Download                                          |
|-----------------|--------------------------------------------------------|
| 2.4.*           | [Clearpay](https://github.com/clearpay/clearpay-magento-2/archive/refs/heads/main.zip) |
| 2.3.*           | [Clearpay:4.*](https://github.com/clearpay/clearpay-magento-2/archive/refs/heads/2.3-main.zip)  |
| < 2.3.0         | [Clearpay:legacy](https://github.com/clearpay/clearpay-magento-2/archive/refs/heads/legacy-main.zip) |
</li>
<li> Unzip the file</li>
<li> Create directory `Clearpay/Clearpay` in: <em>[MAGENTO]/app/code/ </em> </li>
<li> Copy the files to `Clearpay/Clearpay` folder </li>
<li> Run Magento install/upgrade scripts: <code><em>php bin/magento setup:upgrade</em></code> </li>
<li> Compile dependency injection: <code><em>php bin/magento setup:di:compile</em></code> </li>
<li> Deploy static view files (production mode only): <code><em>php bin/magento setup:static-content:deploy</em></code> </li>
<li> Flush Magento cache: <code><em>php bin/magento cache:flush</em></code></li>
</ol>

## Clearpay Merchant Setup
Complete the below steps to configure the merchant’s Clearpay Merchant Credentials in Magento Admin.
<em><strong>Note:</strong> Prerequisite for this section is to obtain an Clearpay Merchant ID and Secret Key from Clearpay.</em>

<ol>
   <li> Navigate to <em>Magento Admin/Stores/Configuration/Sales/Payment Methods/Clearpay</em> </li>
	<li> Enter the <em>Merchant ID</em> and <em>Merchant Key</em>. </li>
	<li> Enable Clearpay plugin using the <em>Enabled</em> checkbox. </li>
	<li> Configure the Clearpay API Mode (<em>Sandbox Mode</em> for testing on a staging instance and <em>Production Mode</em> for a live website and legitimate transactions). </li>
	<li> Save the configuration. </li>
	<li> Navigate to <em>Magento Admin/System/Tools/Cache Management</em> </li>
    <li> Click <em>Flush Magento Cache</em> button</li>
</ol>

## Upgrade

### Composer Upgrade (Recommended)
<p> This section outlines the steps to upgrade the currently installed Clearpay plugin version using composer. </p>
<p> <strong>Notes:</strong> Prerequisite for this section is that the module should be installed using composer. Please see section <em>'Install using Composer'</em> for guidelines to install Clearpay module using composer.</p>
<p>[MAGENTO] refers to the root folder where Magento is installed. </p>

<ol>
	<li> Open Command Line Interface and navigate to the Magento directory on your server</li>
	<li> In CLI, run the below command to update Clearpay module:  
<br/>The right installation command is dependent on your Magento 2 version:

| Magento version | Command to run                                              |
|-----------------|-------------------------------------------------------------|
| 2.4.*           | composer require clearpay/module-clearpay:^5         |
| 2.3.*           | composer require clearpay/module-clearpay:^4         |
| < 2.3.0         | composer require clearpay/module-clearpay:^3         |
 </li>
<li> Make sure that Composer finished the update without errors </li>
<li> Run Magento install/upgrade scripts: <code><em>php bin/magento setup:upgrade</em></code> </li>
<li> Compile dependency injection: <code><em>php bin/magento setup:di:compile</em></code> </li>
<li> Deploy static view files (production mode only): <code><em>php bin/magento setup:static-content:deploy</em></code> </li>
<li> Flush Magento cache: <code><em>php bin/magento cache:flush</em></code></li>
</ol>

### Manual Upgrade
<p>This section outlines the steps to upgrade the currently installed Clearpay plugin version.<br/>
The process of upgrading the Clearpay plugin version involves the complete removal of Clearpay plugin files. <br/>
</p>
<em><strong>Note:</strong>  [MAGENTO] refers to the root folder where Magento is installed. </em>

<ol>
	<li> Remove Files in: <em>[MAGENTO]/app/code/Clearpay/Clearpay</em></li>
	<li> Download the Magento-Clearpay plugin - Available as a .zip or tar.gz file from the Clearpay GitHub directory.
 <br/>The right Clearpay module upgradation is dependent on your Magento 2 version:

| Magento version | Download                                          |
|-----------------|--------------------------------------------------------|
| 2.4.*           | [Clearpay:latest](https://github.com/clearpay/clearpay-magento-2/archive/refs/heads/main.zip) |
| 2.3.*           | [Clearpay:4.*](https://github.com/clearpay/clearpay-magento-2/archive/refs/heads/2.3-main.zip)  |
| < 2.3.0         | [Clearpay:legacy](https://github.com/clearpay/clearpay-magento-2/archive/refs/heads/legacy-main.zip) |
   </li>
	<li> Unzip the file </li>
	<li> Copy the files in folder to:  <em>[MAGENTO]/app/code/Clearpay/Clearpay</em> </li>
	<li> Open Command Line Interface </li>
	<li> In CLI, run the command to enable Clearpay module: <code><em>php bin/magento module:enable Clearpay_Clearpay</em></code> </li>
	<li> Run Magento install/upgrade scripts: <code><em>php bin/magento setup:upgrade</em></code> </li>
   <li> Compile dependency injection: <code><em>php bin/magento setup:di:compile</em></code> </li>
   <li> Deploy static view files (production mode only): <code><em>php bin/magento setup:static-content:deploy</em></code> </li>
   <li> Flush Magento cache: <code><em>php bin/magento cache:flush</em></code></li>
</ol>

## Uninstall

<ol>
<li> From the CLI, run the following commands to uninstall Clearpay module: <code><em> bin/magento module:uninstall Clearpay_Clearpay</em></code>
</li>
<li> Run Magento install/upgrade scripts: <code><em>php bin/magento setup:upgrade</em></code> </li>
   <li> Compile dependency injection: <code><em>php bin/magento setup:di:compile</em></code> </li>
   <li> Deploy static view files (production mode only): <code><em>php bin/magento setup:static-content:deploy</em></code> </li>
   <li> Flush Magento cache: <code><em>php bin/magento cache:flush</em></code></li>
</ol>
