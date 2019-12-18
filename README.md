<h2> 1.1 New Clearpay Installation </h2>
<p>This section outlines the steps to install the Clearpay plugin for the first time.</p>

<p> Note: [MAGENTO] refers to the root folder where Magento is installed. </p>

<ol>
	<li> Download the Magento-Clearpay plugin - Available as a .zip or tar.gz file from the Clearpay GitHub directory. </li>
	<li> Unzip the file </li>
	<li> Create directory Clearpay/Clearpay in: <br/> <em>[MAGENTO]/app/code/</em></li>
	<li> Copy the files to <em>'Clearpay/Clearpay'</em> folder </li>
	<li> Open Command Line Interface </li>
	<li> In CLI, run the below command to enable Clearpay module: <br/> <em>php bin/magento module:enable Clearpay_Clearpay</em> </li>
	<li> In CLI, run the Magento setup upgrade: <br/> <em>php bin/magento setup:upgrade</em> </li>
	<li> In CLI, run the Magento Dependencies Injection Compile: <br/> <em>php bin/magento setup:di:compile</em> </li>
	<li> In CLI, run the Magento Static Content deployment: <br/> <em>php bin/magento setup:static-content:deploy</em> </li>
	<li> Login to Magento Admin and navigate to System/Cache Management </li>
	<li> Flush the cache storage by selecting Flush Cache Storage </li>
</ol>

<h2> 1.2	Clearpay Merchant Setup </h2>
<p> Complete the below steps to configure the merchant’s Clearpay Merchant Credentials in Magento Admin. </p>
<p> Note: Prerequisite for this section is to obtain a Clearpay Merchant ID and Secret Key from Clearpay. </p>

<ol>
	<li> Navigate to <em>Magento Admin/Stores/Configuration/Sales/Payment Methods/Clearpay</em> </li>
	<li> Enter the <em>Merchant ID</em> and <em>Merchant Key</em>. </li>
	<li> Enable Clearpay plugin using the <em>Enabled</em> checkbox. </li>
	<li> Configure the Clearpay API Mode (<em>Sandbox Mode</em> for testing on a staging instance and <em>Production Mode</em> for a live website and legitimate transactions). </li>
	<li> Save the configuration. </li>
	<li> Click the <em>Update Limits</em> button to retrieve the Minimum and Maximum Clearpay Order values.</li>
</ol>

<h2> 1.3	Upgrade Of Clearpay Installation </h2>
<p> This section outlines the steps to upgrade the currently installed Clearpay plugin version. </p>
<p> The process of upgrading the Clearpay plugin version involves the complete removal of Clearpay plugin files. </p>
<p> Note: [MAGENTO] refers to the root folder where Magento is installed. </p>

<ol>
	<li> Remove Files in: <em>[MAGENTO]/app/code/Clearpay/Clearpay</em></li>
	<li> Download the Magento-Clearpay plugin - Available as a .zip or tar.gz file from the Clearpay GitHub directory. </li>
	<li> Unzip the file </li>
	<li> Copy the files in folder to: <br/> <em>[MAGENTO]/app/code/Clearpay/Clearpay</em> </li>
	<li> Open Command Line Interface </li>
	<li> In CLI, run the below command to enable Clearpay module: <br/> <em>php bin/magento module:enable Clearpay_Clearpay</em> </li>
	<li> In CLI, run the Magento setup upgrade: <br/> <em>php bin/magento setup:upgrade</em> </li>
	<li> In CLI, run the Magento Dependencies Injection Compile: <br/> <em>php bin/magento setup:di:compile</em> </li>
	<li> In CLI, run the Magento Static Content deployment: <br/> <em>php bin/magento setup:static-content:deploy</em> </li>
	<li> Login to Magento Admin and navigate to System/Cache Management </li>
	<li> Flush the cache storage by selecting Flush Cache Storage </li>
</ol>