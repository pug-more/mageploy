![Logo of Mageploy](https://raw.github.com/wiki/pug-more/mageploy/mageploy_128_noborder.png)

Mageploy is an extension for Magento deployment automation based on Admin actions tracking and replication between different project environments.

How does it work?
-----------------
Basically it's a tracker of invocations of Magento Controller Actions. It's based on the recording of actions in two files:

* var/mageploy/mageploy_all.csv
* var/mageploy/mageploy_executed.csv

The **mageploy_all.csv** file is **global** and should be put **under version control**. This file keeps track of each action invoked, storing parameters converted and serialized.

The **mageploy_executed.csv** is **local** and should **not be put under version control**. This file keeps track of actions invoked locally and is used to avoid invocations of already invoked actions.

Once you install mageploy and activate tracking (active by default), Mageploy's Trackers will store action invocations.

Once you commit and push your changes you will also push the **mageploy_all.csv**. Developers which will pull your changes will get the global list of actions updated and can replicate missing invocations by using the command line tool **shell/mageploy.php**.

To learn more about Mageploy, please refer to the [Official Documentation](https://github.com/pug-more/mageploy/wiki).

AS-IS
-----
At the moment the following trackers have been developed for (not tested so much):

* Attributes
* Attribute Sets
* System Config (uncomplete)
* Categories (uncomplete)

TO-BE
-----
The System\Config tracker is only a POC. There are a lot of sections and groups in the System\Config and we should provide encorders/decoders for all of them.

For example in some circumstances you can perform file ulpoad; this is not handled. At the same time IDs are not translated into UUIDs but there can be IDs which are specific to current installation. To handle all these cases the System\Config tracker should be splitted into sections/groups trackers, each of them applying its enconding/deconding policy.
In Categories tracker file uploads are not handled yet.

So many more trackers should be developed for:

* Complete System Config Sections/Groups
* Complete Categories
* Taxes
* URL Rewrites
* Promotions
* CMS Pages
* Transactional Emails
* Stores
* Order Statuses
